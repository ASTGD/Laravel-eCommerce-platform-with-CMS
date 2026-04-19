<?php

namespace Platform\CommerceCore\Services;

use Illuminate\Support\Collection;
use Platform\CommerceCore\Models\ShipmentEvent;
use Platform\CommerceCore\Models\ShipmentRecord;
use Webkul\Sales\Models\Order;

class CustomerShipmentTrackingService
{
    public function forOrder(Order $order): Collection
    {
        return $this->baseQuery()
            ->where('order_id', $order->id)
            ->latest('id')
            ->get()
            ->map(fn (ShipmentRecord $shipmentRecord) => $this->transformRecord($shipmentRecord));
    }

    public function lookupPublic(string $reference, string $phone): ?array
    {
        $reference = trim($reference);
        $normalizedPhone = $this->normalizePhone($phone);

        $shipmentRecords = $this->baseQuery()
            ->where(function ($query) use ($reference) {
                $query->where('tracking_number', $reference)
                    ->orWhereHas('order', fn ($orderQuery) => $orderQuery->where('increment_id', $reference));
            })
            ->latest('id')
            ->get()
            ->filter(fn (ShipmentRecord $shipmentRecord) => $this->matchesPhone($shipmentRecord, $normalizedPhone))
            ->values();

        if ($shipmentRecords->isEmpty()) {
            return null;
        }

        $order = $shipmentRecords->first()->order;

        return [
            'reference' => $reference,
            'order_increment_id' => $order?->increment_id,
            'shipments' => $shipmentRecords->map(fn (ShipmentRecord $shipmentRecord) => $this->transformRecord($shipmentRecord)),
        ];
    }

    protected function statusLabel(ShipmentRecord $shipmentRecord): string
    {
        if (
            $shipmentRecord->status === ShipmentRecord::STATUS_DELIVERY_FAILED
            && $shipmentRecord->requires_reattempt
        ) {
            return 'Reattempt Required';
        }

        return match ($shipmentRecord->status) {
            ShipmentRecord::STATUS_DRAFT => 'Awaiting Shipment',
            ShipmentRecord::STATUS_READY_FOR_PICKUP => 'Preparing Shipment',
            ShipmentRecord::STATUS_HANDED_TO_CARRIER => 'Shipped',
            ShipmentRecord::STATUS_IN_TRANSIT => 'In Transit',
            ShipmentRecord::STATUS_OUT_FOR_DELIVERY => 'Out for Delivery',
            ShipmentRecord::STATUS_DELIVERED => 'Delivered',
            ShipmentRecord::STATUS_DELIVERY_FAILED => 'Delivery Failed',
            ShipmentRecord::STATUS_RETURNED => 'Returned to Origin',
            ShipmentRecord::STATUS_CANCELED => 'Shipment Canceled',
            default => $shipmentRecord->status_label,
        };
    }

    protected function transformEvent(ShipmentRecord $shipmentRecord, ShipmentEvent $event): ?array
    {
        $label = match ($event->event_type) {
            ShipmentEvent::EVENT_SHIPMENT_CREATED => 'Shipment created',
            ShipmentEvent::EVENT_STATUS_UPDATED => $this->statusLabelFromKey($event->status_after_event, $shipmentRecord->requires_reattempt),
            ShipmentEvent::EVENT_ARRIVED_DESTINATION_HUB => 'Arrived at destination hub',
            ShipmentEvent::EVENT_DELIVERY_ATTEMPTED => 'Delivery attempt recorded',
            ShipmentEvent::EVENT_CUSTOMER_UNREACHABLE => 'Customer unreachable for delivery',
            ShipmentEvent::EVENT_CUSTOMER_REFUSED => 'Delivery refused by customer',
            ShipmentEvent::EVENT_REATTEMPT_APPROVED => 'Reattempt approved',
            ShipmentEvent::EVENT_RETURN_INITIATED => 'Return to origin initiated',
            ShipmentEvent::EVENT_RETURN_COMPLETED => 'Returned to origin',
            default => null,
        };

        if (! $label) {
            return null;
        }

        return [
            'label' => $label,
            'occurred_at' => $event->event_at,
        ];
    }

    protected function transformRecord(ShipmentRecord $shipmentRecord): array
    {
        return [
            'id' => $shipmentRecord->id,
            'carrier_name' => $shipmentRecord->carrier?->name ?? $shipmentRecord->carrier_name_snapshot,
            'tracking_number' => $shipmentRecord->tracking_number,
            'carrier_tracking_url' => $shipmentRecord->carrier?->trackingUrl($shipmentRecord->tracking_number),
            'status' => $shipmentRecord->status,
            'status_label' => $this->statusLabel($shipmentRecord),
            'events' => $shipmentRecord->events
                ->map(fn (ShipmentEvent $event) => $this->transformEvent($shipmentRecord, $event))
                ->filter()
                ->values(),
        ];
    }

    protected function statusLabelFromKey(?string $status, bool $requiresReattempt = false): ?string
    {
        if (! $status) {
            return null;
        }

        if (
            $status === ShipmentRecord::STATUS_DELIVERY_FAILED
            && $requiresReattempt
        ) {
            return 'Reattempt Required';
        }

        return match ($status) {
            ShipmentRecord::STATUS_DRAFT => 'Shipment created',
            ShipmentRecord::STATUS_READY_FOR_PICKUP => 'Preparing shipment',
            ShipmentRecord::STATUS_HANDED_TO_CARRIER => 'Shipped',
            ShipmentRecord::STATUS_IN_TRANSIT => 'In transit',
            ShipmentRecord::STATUS_OUT_FOR_DELIVERY => 'Out for delivery',
            ShipmentRecord::STATUS_DELIVERED => 'Delivered',
            ShipmentRecord::STATUS_DELIVERY_FAILED => 'Delivery failed',
            ShipmentRecord::STATUS_RETURNED => 'Returned to origin',
            ShipmentRecord::STATUS_CANCELED => 'Shipment canceled',
            default => null,
        };
    }

    protected function baseQuery()
    {
        return ShipmentRecord::query()->with([
            'order',
            'carrier',
            'events' => fn ($query) => $query->orderBy('event_at')->orderBy('id'),
        ]);
    }

    protected function matchesPhone(ShipmentRecord $shipmentRecord, string $normalizedPhone): bool
    {
        if ($normalizedPhone === '') {
            return false;
        }

        return $this->normalizePhone((string) $shipmentRecord->recipient_phone) === $normalizedPhone;
    }

    protected function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: '';

        if (str_starts_with($digits, '880') && strlen($digits) >= 13) {
            return substr($digits, -11);
        }

        if (str_starts_with($digits, '88') && strlen($digits) >= 13) {
            return substr($digits, -11);
        }

        if (strlen($digits) > 11) {
            return substr($digits, -11);
        }

        return $digits;
    }
}
