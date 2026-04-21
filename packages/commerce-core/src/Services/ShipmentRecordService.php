<?php

namespace Platform\CommerceCore\Services;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Platform\CommerceCore\Models\ShipmentCarrier;
use Platform\CommerceCore\Models\ShipmentEvent;
use Platform\CommerceCore\Models\ShipmentRecord;
use Platform\CommerceCore\Models\ShipmentRecordItem;
use Platform\CommerceCore\Repositories\ShipmentRecordRepository;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderAddress;
use Webkul\Sales\Models\Shipment;

class ShipmentRecordService
{
    public function __construct(
        protected ShipmentRecordRepository $shipmentRecordRepository,
        protected CodSettlementService $codSettlementService,
        protected ShipmentCommunicationService $shipmentCommunicationService,
    ) {}

    public function syncFromNativeShipment(Shipment $shipment, array $context = []): ShipmentRecord
    {
        $shipment->loadMissing([
            'order.payment',
            'order.addresses',
            'items',
            'inventory_source',
        ]);

        return DB::transaction(function () use ($shipment, $context) {
            $order = $shipment->order;
            $shippingAddress = $order->shipping_address;
            $selectedCarrierId = isset($context['carrier_id']) ? (int) $context['carrier_id'] : null;
            $carrier = $this->resolveCarrier($selectedCarrierId, $shipment->carrier_code, $shipment->carrier_title);
            $actorAdminId = Auth::guard('admin')->id();
            $publicTrackingUrl = trim((string) ($context['public_tracking_url'] ?? ''));
            $bookingNote = trim((string) ($context['note'] ?? ''));

            $record = ShipmentRecord::query()->firstOrNew([
                'native_shipment_id' => $shipment->id,
            ]);

            $status = $record->exists ? $record->status : ShipmentRecord::STATUS_HANDED_TO_CARRIER;
            $carrierFeeAmount = (float) ($record->carrier_fee_amount ?? $order->base_shipping_amount ?? 0);
            $codExpectedAmount = $this->resolveExpectedCodAmount($order);
            $codFeeAmount = (float) ($record->cod_fee_amount ?? $carrier?->default_cod_fee_amount ?? 0);
            $returnFeeAmount = (float) ($record->return_fee_amount ?? $carrier?->default_return_fee_amount ?? 0);

            $record->fill([
                'order_id' => $order->id,
                'shipment_carrier_id' => $carrier?->id,
                'inventory_source_id' => $shipment->inventory_source_id,
                'updated_by_admin_id' => $actorAdminId,
                'status' => $status,
                'carrier_name_snapshot' => $carrier?->name ?: $shipment->carrier_title,
                'tracking_number' => $shipment->track_number,
                'public_tracking_url' => $publicTrackingUrl !== '' ? $publicTrackingUrl : $record->public_tracking_url,
                'inventory_source_name' => $shipment->inventory_source?->name ?: $shipment->inventory_source_name,
                'origin_label' => $shipment->inventory_source?->name ?: $shipment->inventory_source_name,
                'destination_country' => $shippingAddress?->country,
                'destination_region' => $shippingAddress?->state,
                'destination_city' => $shippingAddress?->city,
                'recipient_name' => $shippingAddress?->name ?: $order->customer_full_name,
                'recipient_phone' => $shippingAddress?->phone,
                'recipient_address' => $shippingAddress?->address,
                'cod_amount_expected' => $codExpectedAmount,
                'carrier_fee_amount' => $carrierFeeAmount,
                'cod_fee_amount' => $codFeeAmount,
                'return_fee_amount' => $returnFeeAmount,
                'net_remittable_amount' => max(0, $codExpectedAmount - $carrierFeeAmount - $codFeeAmount - $returnFeeAmount),
                'handed_over_at' => $record->handed_over_at ?: now(),
                'notes' => $bookingNote !== '' ? $bookingNote : $record->notes,
            ]);

            if (! $record->exists) {
                $record->created_by_admin_id = $actorAdminId;
            }

            $record->save();

            $this->syncItems($record, $shipment);
            $this->ensureInitialEvent($record, $actorAdminId, $bookingNote);
            $this->codSettlementService->syncFromShipmentRecord($record, $actorAdminId);

            return $record->fresh(['carrier', 'items', 'events', 'communications', 'nativeShipment', 'order', 'codSettlement']);
        });
    }

    public function forOrder(Order $order): Collection
    {
        return ShipmentRecord::query()
            ->with(['carrier', 'events'])
            ->where('order_id', $order->id)
            ->latest()
            ->get();
    }

    public function updateStatus(ShipmentRecord $shipmentRecord, string $status, ?string $note = null, ?int $actorAdminId = null): ShipmentRecord
    {
        return $this->appendEvent(
            $shipmentRecord,
            ShipmentEvent::EVENT_STATUS_UPDATED,
            $note,
            $actorAdminId,
            $status,
            [
                'tracking_number' => $shipmentRecord->tracking_number,
            ],
        );
    }

    public function recordDeliveryFailure(
        ShipmentRecord $shipmentRecord,
        string $failureReason,
        ?string $note = null,
        bool $requiresReattempt = false,
        ?int $actorAdminId = null,
    ): ShipmentRecord {
        $result = DB::transaction(function () use ($shipmentRecord, $failureReason, $note, $requiresReattempt, $actorAdminId) {
            $attemptNumber = ((int) $shipmentRecord->delivery_attempt_count) + 1;
            $eventAt = now();

            $shipmentRecord->fill([
                'status' => ShipmentRecord::STATUS_DELIVERY_FAILED,
                'updated_by_admin_id' => $actorAdminId,
                'delivery_attempt_count' => $attemptNumber,
                'delivery_failure_reason' => $failureReason,
                'requires_reattempt' => $requiresReattempt,
                'last_delivery_attempt_at' => $eventAt,
            ]);

            $shipmentRecord->save();

            $event = ShipmentEvent::query()->create([
                'shipment_record_id' => $shipmentRecord->id,
                'actor_admin_id' => $actorAdminId,
                'event_type' => $this->resolveFailureEventType($failureReason),
                'status_after_event' => ShipmentRecord::STATUS_DELIVERY_FAILED,
                'event_at' => $eventAt,
                'note' => $note,
                'meta' => [
                    'tracking_number' => $shipmentRecord->tracking_number,
                    'attempt_count' => $attemptNumber,
                    'failure_reason' => $failureReason,
                    'requires_reattempt' => $requiresReattempt,
                ],
            ]);

            $this->codSettlementService->syncFromShipmentRecord($shipmentRecord, $actorAdminId);

            return [
                'shipment_record' => $shipmentRecord->fresh(['carrier', 'items', 'events', 'communications', 'nativeShipment', 'order', 'codSettlement']),
                'event_id' => $event->id,
            ];
        });

        $this->shipmentCommunicationService->dispatchForPersistedEvent($result['shipment_record']->id, $result['event_id']);

        return $result['shipment_record'];
    }

    public function approveReattempt(
        ShipmentRecord $shipmentRecord,
        ?string $note = null,
        ?int $actorAdminId = null,
    ): ShipmentRecord {
        $result = DB::transaction(function () use ($shipmentRecord, $note, $actorAdminId) {
            $shipmentRecord->fill([
                'status' => ShipmentRecord::STATUS_OUT_FOR_DELIVERY,
                'updated_by_admin_id' => $actorAdminId,
                'requires_reattempt' => false,
            ]);

            $shipmentRecord->save();

            $event = ShipmentEvent::query()->create([
                'shipment_record_id' => $shipmentRecord->id,
                'actor_admin_id' => $actorAdminId,
                'event_type' => ShipmentEvent::EVENT_REATTEMPT_APPROVED,
                'status_after_event' => ShipmentRecord::STATUS_OUT_FOR_DELIVERY,
                'event_at' => now(),
                'note' => $note,
                'meta' => [
                    'tracking_number' => $shipmentRecord->tracking_number,
                    'attempt_count' => $shipmentRecord->delivery_attempt_count,
                ],
            ]);

            $this->codSettlementService->syncFromShipmentRecord($shipmentRecord, $actorAdminId);

            return [
                'shipment_record' => $shipmentRecord->fresh(['carrier', 'items', 'events', 'communications', 'nativeShipment', 'order', 'codSettlement']),
                'event_id' => $event->id,
            ];
        });

        $this->shipmentCommunicationService->dispatchForPersistedEvent($result['shipment_record']->id, $result['event_id']);

        return $result['shipment_record'];
    }

    public function initiateReturn(
        ShipmentRecord $shipmentRecord,
        ?string $note = null,
        ?int $actorAdminId = null,
    ): ShipmentRecord {
        $result = DB::transaction(function () use ($shipmentRecord, $note, $actorAdminId) {
            $eventAt = now();

            $shipmentRecord->fill([
                'updated_by_admin_id' => $actorAdminId,
                'requires_reattempt' => false,
                'return_initiated_at' => $shipmentRecord->return_initiated_at ?: $eventAt,
            ]);

            $shipmentRecord->save();

            $event = ShipmentEvent::query()->create([
                'shipment_record_id' => $shipmentRecord->id,
                'actor_admin_id' => $actorAdminId,
                'event_type' => ShipmentEvent::EVENT_RETURN_INITIATED,
                'status_after_event' => $shipmentRecord->status,
                'event_at' => $eventAt,
                'note' => $note,
                'meta' => [
                    'tracking_number' => $shipmentRecord->tracking_number,
                    'failure_reason' => $shipmentRecord->delivery_failure_reason,
                ],
            ]);

            $this->codSettlementService->syncFromShipmentRecord($shipmentRecord, $actorAdminId);

            return [
                'shipment_record' => $shipmentRecord->fresh(['carrier', 'items', 'events', 'communications', 'nativeShipment', 'order', 'codSettlement']),
                'event_id' => $event->id,
            ];
        });

        $this->shipmentCommunicationService->dispatchForPersistedEvent($result['shipment_record']->id, $result['event_id']);

        return $result['shipment_record'];
    }

    public function completeReturn(
        ShipmentRecord $shipmentRecord,
        ?string $note = null,
        ?int $actorAdminId = null,
    ): ShipmentRecord {
        return $this->appendEvent(
            $shipmentRecord,
            ShipmentEvent::EVENT_RETURN_COMPLETED,
            $note,
            $actorAdminId,
            ShipmentRecord::STATUS_RETURNED,
            [
                'failure_reason' => $shipmentRecord->delivery_failure_reason,
                'attempt_count' => $shipmentRecord->delivery_attempt_count,
            ],
        );
    }

    public function updateBookingReferences(
        ShipmentRecord $shipmentRecord,
        array $attributes,
        ?string $note = null,
        ?int $actorAdminId = null,
    ): ShipmentRecord {
        return DB::transaction(function () use ($shipmentRecord, $attributes, $note, $actorAdminId) {
            $shipmentRecord->fill([
                'carrier_booking_reference' => $attributes['carrier_booking_reference'] ?? null,
                'carrier_consignment_id' => $attributes['carrier_consignment_id'] ?? null,
                'carrier_invoice_reference' => $attributes['carrier_invoice_reference'] ?? null,
                'carrier_booked_at' => isset($attributes['carrier_booked_at']) && $attributes['carrier_booked_at']
                    ? Carbon::parse($attributes['carrier_booked_at'])
                    : null,
                'updated_by_admin_id' => $actorAdminId,
            ]);

            $referenceFields = [
                'carrier_booking_reference',
                'carrier_consignment_id',
                'carrier_invoice_reference',
                'carrier_booked_at',
            ];

            $referencesChanged = collect($referenceFields)
                ->contains(fn (string $field) => $shipmentRecord->isDirty($field));

            if (! $referencesChanged && blank($note)) {
                return $shipmentRecord->fresh(['carrier', 'items', 'events', 'communications', 'nativeShipment', 'order', 'codSettlement']);
            }

            if ($shipmentRecord->isDirty()) {
                $shipmentRecord->save();
            }

            ShipmentEvent::query()->create([
                'shipment_record_id' => $shipmentRecord->id,
                'actor_admin_id' => $actorAdminId,
                'event_type' => ShipmentEvent::EVENT_BOOKING_REFERENCES_UPDATED,
                'status_after_event' => null,
                'event_at' => now(),
                'note' => $note ?: 'Carrier booking references updated.',
                'meta' => [
                    'tracking_number' => $shipmentRecord->tracking_number,
                    'carrier_booking_reference' => $shipmentRecord->carrier_booking_reference,
                    'carrier_consignment_id' => $shipmentRecord->carrier_consignment_id,
                    'carrier_invoice_reference' => $shipmentRecord->carrier_invoice_reference,
                    'carrier_booked_at' => $shipmentRecord->carrier_booked_at?->toDateTimeString(),
                ],
            ]);

            return $shipmentRecord->fresh(['carrier', 'items', 'events', 'communications', 'nativeShipment', 'order', 'codSettlement']);
        });
    }

    public function captureCarrierBooking(
        ShipmentRecord $shipmentRecord,
        array $attributes,
        ?string $note = null,
        ?int $actorAdminId = null,
        array $meta = [],
    ): ShipmentRecord {
        return DB::transaction(function () use ($shipmentRecord, $attributes, $note, $actorAdminId, $meta) {
            $shipmentRecord->loadMissing('nativeShipment');

            $trackingNumber = array_key_exists('tracking_number', $attributes)
                ? trim((string) $attributes['tracking_number'])
                : null;

            $shipmentRecord->fill([
                'carrier_booking_reference' => $attributes['carrier_booking_reference'] ?? $shipmentRecord->carrier_booking_reference,
                'carrier_consignment_id' => $attributes['carrier_consignment_id'] ?? $shipmentRecord->carrier_consignment_id,
                'carrier_invoice_reference' => $attributes['carrier_invoice_reference'] ?? $shipmentRecord->carrier_invoice_reference,
                'carrier_booked_at' => isset($attributes['carrier_booked_at']) && $attributes['carrier_booked_at']
                    ? Carbon::parse($attributes['carrier_booked_at'])
                    : ($shipmentRecord->carrier_booked_at ?: now()),
                'updated_by_admin_id' => $actorAdminId,
            ]);

            if ($trackingNumber !== null && $trackingNumber !== '') {
                $shipmentRecord->tracking_number = $trackingNumber;
            }

            $trackedFields = [
                'tracking_number',
                'carrier_booking_reference',
                'carrier_consignment_id',
                'carrier_invoice_reference',
                'carrier_booked_at',
            ];

            $changed = collect($trackedFields)
                ->contains(fn (string $field) => $shipmentRecord->isDirty($field));

            if (! $changed && blank($note)) {
                return $shipmentRecord->fresh(['carrier', 'items', 'events', 'communications', 'nativeShipment', 'order', 'codSettlement']);
            }

            if ($shipmentRecord->isDirty()) {
                $shipmentRecord->save();
            }

            if (
                $shipmentRecord->nativeShipment
                && $trackingNumber !== null
                && $trackingNumber !== ''
                && $shipmentRecord->nativeShipment->track_number !== $trackingNumber
            ) {
                $shipmentRecord->nativeShipment->track_number = $trackingNumber;
                $shipmentRecord->nativeShipment->save();
            }

            ShipmentEvent::query()->create([
                'shipment_record_id' => $shipmentRecord->id,
                'actor_admin_id' => $actorAdminId,
                'event_type' => ShipmentEvent::EVENT_CARRIER_BOOKED,
                'status_after_event' => null,
                'event_at' => now(),
                'note' => $note ?: 'Carrier booking created from the courier API.',
                'meta' => array_merge([
                    'tracking_number' => $shipmentRecord->tracking_number,
                    'carrier_booking_reference' => $shipmentRecord->carrier_booking_reference,
                    'carrier_consignment_id' => $shipmentRecord->carrier_consignment_id,
                    'carrier_invoice_reference' => $shipmentRecord->carrier_invoice_reference,
                    'carrier_booked_at' => $shipmentRecord->carrier_booked_at?->toDateTimeString(),
                ], $meta),
            ]);

            return $shipmentRecord->fresh(['carrier', 'items', 'events', 'communications', 'nativeShipment', 'order', 'codSettlement']);
        });
    }

    public function appendEvent(
        ShipmentRecord $shipmentRecord,
        string $eventType,
        ?string $note = null,
        ?int $actorAdminId = null,
        ?string $statusAfterEvent = null,
        array $meta = [],
    ): ShipmentRecord {
        $result = DB::transaction(function () use ($shipmentRecord, $eventType, $note, $actorAdminId, $statusAfterEvent, $meta) {
            $eventAt = now();
            $resolvedStatus = $statusAfterEvent ?: $shipmentRecord->status;

            if ($statusAfterEvent) {
                $shipmentRecord->status = $statusAfterEvent;
            }

            $shipmentRecord->updated_by_admin_id = $actorAdminId;

            $this->applyStatusSideEffects($shipmentRecord, $resolvedStatus, $eventAt);

            if ($shipmentRecord->isDirty()) {
                $shipmentRecord->save();
            }

            $event = ShipmentEvent::query()->create([
                'shipment_record_id' => $shipmentRecord->id,
                'actor_admin_id' => $actorAdminId,
                'event_type' => $eventType,
                'status_after_event' => $resolvedStatus,
                'event_at' => $eventAt,
                'note' => $note,
                'meta' => array_merge([
                    'tracking_number' => $shipmentRecord->tracking_number,
                ], $meta),
            ]);

            $this->codSettlementService->syncFromShipmentRecord($shipmentRecord, $actorAdminId);

            return [
                'shipment_record' => $shipmentRecord->fresh(['carrier', 'items', 'events', 'communications', 'nativeShipment', 'order', 'codSettlement']),
                'event_id' => $event->id,
            ];
        });

        $this->shipmentCommunicationService->dispatchForPersistedEvent($result['shipment_record']->id, $result['event_id']);

        return $result['shipment_record'];
    }

    protected function resolveCarrier(?int $carrierId, ?string $carrierCode, ?string $carrierTitle): ?ShipmentCarrier
    {
        if ($carrierId) {
            $carrier = ShipmentCarrier::query()->find($carrierId);

            if ($carrier) {
                return $carrier;
            }
        }

        if ($carrierCode) {
            $carrier = ShipmentCarrier::query()
                ->whereRaw('LOWER(code) = ?', [mb_strtolower(trim($carrierCode))])
                ->first();

            if ($carrier) {
                return $carrier;
            }
        }

        if (! $carrierTitle) {
            return null;
        }

        $normalizedTitle = mb_strtolower(trim($carrierTitle));

        return ShipmentCarrier::query()
            ->whereRaw('LOWER(code) = ?', [$normalizedTitle])
            ->orWhereRaw('LOWER(name) = ?', [$normalizedTitle])
            ->first();
    }

    protected function resolveExpectedCodAmount(Order $order): float
    {
        if ($order->payment?->method !== 'cashondelivery') {
            return 0.0;
        }

        return (float) ($order->base_grand_total ?? 0);
    }

    protected function syncItems(ShipmentRecord $shipmentRecord, Shipment $shipment): void
    {
        $seenItemIds = [];

        foreach ($shipment->items as $nativeShipmentItem) {
            $recordItem = ShipmentRecordItem::query()->updateOrCreate(
                [
                    'native_shipment_item_id' => $nativeShipmentItem->id,
                ],
                [
                    'shipment_record_id' => $shipmentRecord->id,
                    'order_item_id' => $nativeShipmentItem->order_item_id,
                    'name' => $nativeShipmentItem->name,
                    'sku' => $nativeShipmentItem->sku,
                    'qty' => $nativeShipmentItem->qty,
                    'weight' => $nativeShipmentItem->weight,
                ]
            );

            $seenItemIds[] = $recordItem->id;
        }

        ShipmentRecordItem::query()
            ->where('shipment_record_id', $shipmentRecord->id)
            ->whereNotIn('id', $seenItemIds)
            ->delete();
    }

    protected function ensureInitialEvent(ShipmentRecord $shipmentRecord, ?int $actorAdminId, ?string $bookingNote = null): void
    {
        if ($shipmentRecord->events()->exists()) {
            return;
        }

        ShipmentEvent::query()->create([
            'shipment_record_id' => $shipmentRecord->id,
            'actor_admin_id' => $actorAdminId,
            'event_type' => ShipmentEvent::EVENT_SHIPMENT_CREATED,
            'status_after_event' => $shipmentRecord->status,
            'event_at' => $shipmentRecord->handed_over_at ?: now(),
            'note' => blank($bookingNote)
                ? 'Operational shipment record created from native shipment creation.'
                : trim((string) $bookingNote),
            'meta' => [
                'native_shipment_id' => $shipmentRecord->native_shipment_id,
                'tracking_number' => $shipmentRecord->tracking_number,
            ],
        ]);
    }

    protected function applyStatusSideEffects(ShipmentRecord $shipmentRecord, string $status, $eventAt): void
    {
        if (
            $status === ShipmentRecord::STATUS_HANDED_TO_CARRIER
            && ! $shipmentRecord->handed_over_at
        ) {
            $shipmentRecord->handed_over_at = $eventAt;
        }

        if (
            $status === ShipmentRecord::STATUS_DELIVERED
            && ! $shipmentRecord->delivered_at
        ) {
            $shipmentRecord->delivered_at = $eventAt;
            $shipmentRecord->cod_amount_collected = $shipmentRecord->cod_amount_expected;
        }

        if (
            $status === ShipmentRecord::STATUS_RETURNED
            && ! $shipmentRecord->returned_at
        ) {
            $shipmentRecord->returned_at = $eventAt;
        }
    }

    protected function resolveFailureEventType(string $failureReason): string
    {
        return match ($failureReason) {
            ShipmentRecord::FAILURE_REASON_CUSTOMER_UNREACHABLE => ShipmentEvent::EVENT_CUSTOMER_UNREACHABLE,
            ShipmentRecord::FAILURE_REASON_CUSTOMER_REFUSED => ShipmentEvent::EVENT_CUSTOMER_REFUSED,
            default => ShipmentEvent::EVENT_DELIVERY_ATTEMPTED,
        };
    }
}
