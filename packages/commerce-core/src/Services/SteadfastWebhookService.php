<?php

namespace Platform\CommerceCore\Services;

use Platform\CommerceCore\Models\ShipmentCarrier;
use Platform\CommerceCore\Models\ShipmentRecord;
use Platform\CommerceCore\Repositories\ShipmentRecordRepository;
use Platform\CommerceCore\ShipmentTracking\CarrierTrackingSyncResult;
use Platform\CommerceCore\ShipmentTracking\ShipmentTrackingWebhookResult;
use Platform\CommerceCore\ShipmentTracking\SteadfastTrackingStatusMapper;

class SteadfastWebhookService
{
    protected const STATUS_PATHS = [
        'delivery_status',
        'current_status',
        'status_name',
        'status_label',
        'status',
        'data.delivery_status',
        'data.status',
        'tracking.delivery_status',
        'tracking.status',
        'parcel.delivery_status',
        'parcel.status',
        'consignment.delivery_status',
        'consignment.status',
    ];

    protected const TRACKING_PATHS = [
        'tracking_number',
        'tracking_code',
        'tracking',
        'data.tracking_number',
        'data.tracking_code',
        'tracking.tracking_number',
        'tracking.tracking_code',
        'consignment.tracking_code',
    ];

    protected const CONSIGNMENT_ID_PATHS = [
        'consignment_id',
        'data.consignment_id',
        'consignment.consignment_id',
        'parcel.consignment_id',
    ];

    protected const INVOICE_PATHS = [
        'invoice',
        'order_reference',
        'merchant_order_id',
        'data.invoice',
        'data.order_reference',
        'consignment.invoice',
    ];

    public function __construct(
        protected ShipmentRecordService $shipmentRecordService,
        protected ShipmentRecordRepository $shipmentRecordRepository,
        protected SteadfastTrackingStatusMapper $statusMapper,
    ) {}

    public function handle(ShipmentCarrier $carrier, array $payload): ShipmentTrackingWebhookResult
    {
        $externalStatus = $this->extractFirstValue($payload, self::STATUS_PATHS);

        if (! $externalStatus) {
            return ShipmentTrackingWebhookResult::invalid('Webhook payload is missing a delivery status.');
        }

        $trackingNumber = $this->extractFirstValue($payload, self::TRACKING_PATHS);
        $consignmentId = $this->extractFirstValue($payload, self::CONSIGNMENT_ID_PATHS);
        $invoice = $this->extractFirstValue($payload, self::INVOICE_PATHS);

        if (! $trackingNumber && ! $consignmentId && ! $invoice) {
            return ShipmentTrackingWebhookResult::invalid('Webhook payload must include a tracking number, consignment id, or invoice reference.');
        }

        $shipmentRecord = $this->resolveShipmentRecord($carrier, $trackingNumber, $consignmentId, $invoice);

        if (! $shipmentRecord) {
            return ShipmentTrackingWebhookResult::accepted(
                'Webhook received but no matching local shipment record was found.',
                externalStatus: $externalStatus,
            );
        }

        $mappedStatus = $this->statusMapper->map($externalStatus);

        if (! $mappedStatus) {
            $message = sprintf(
                'Steadfast webhook received external status "%s", but no local status mapping exists yet.',
                $externalStatus,
            );

            $this->persistSyncState($shipmentRecord, CarrierTrackingSyncResult::STATUS_PENDING, $message);

            return ShipmentTrackingWebhookResult::accepted($message, $shipmentRecord->id, $externalStatus);
        }

        if ($mappedStatus === $shipmentRecord->status) {
            $message = sprintf(
                'Steadfast webhook external status "%s" already matches "%s".',
                $externalStatus,
                $shipmentRecord->status_label,
            );

            $this->persistSyncState($shipmentRecord, CarrierTrackingSyncResult::STATUS_SYNCED, $message);

            return ShipmentTrackingWebhookResult::ok($message, $shipmentRecord->id, $externalStatus);
        }

        if ($this->statusMapper->wouldDowngrade($shipmentRecord->status, $mappedStatus)) {
            $message = sprintf(
                'Steadfast webhook external status "%s" maps to "%s", but the current shipment status "%s" was kept to avoid a downgrade.',
                $externalStatus,
                ShipmentRecord::statusLabels()[$mappedStatus] ?? $mappedStatus,
                $shipmentRecord->status_label,
            );

            $this->persistSyncState($shipmentRecord, CarrierTrackingSyncResult::STATUS_SYNCED, $message);

            return ShipmentTrackingWebhookResult::ok($message, $shipmentRecord->id, $externalStatus);
        }

        $shipmentRecord = $this->shipmentRecordService->updateStatus(
            $shipmentRecord,
            $mappedStatus,
            sprintf(
                'Steadfast webhook mapped external status "%s" to "%s".',
                $externalStatus,
                ShipmentRecord::statusLabels()[$mappedStatus] ?? $mappedStatus,
            ),
            null,
        );

        $message = sprintf(
            'Steadfast webhook synced external status "%s" to "%s".',
            $externalStatus,
            ShipmentRecord::statusLabels()[$mappedStatus] ?? $mappedStatus,
        );

        $this->persistSyncState($shipmentRecord, CarrierTrackingSyncResult::STATUS_SYNCED, $message);

        return ShipmentTrackingWebhookResult::ok($message, $shipmentRecord->id, $externalStatus);
    }

    protected function resolveShipmentRecord(
        ShipmentCarrier $carrier,
        ?string $trackingNumber,
        ?string $consignmentId,
        ?string $invoice,
    ): ?ShipmentRecord
    {
        if ($consignmentId) {
            $shipmentRecord = ShipmentRecord::query()
                ->where('shipment_carrier_id', $carrier->id)
                ->where('carrier_consignment_id', $consignmentId)
                ->first();

            if ($shipmentRecord) {
                return $shipmentRecord;
            }
        }

        if ($trackingNumber) {
            $shipmentRecord = ShipmentRecord::query()
                ->where('shipment_carrier_id', $carrier->id)
                ->where('tracking_number', $trackingNumber)
                ->first();

            if ($shipmentRecord) {
                return $shipmentRecord;
            }
        }

        if (! $invoice) {
            return null;
        }

        return ShipmentRecord::query()
            ->where('shipment_carrier_id', $carrier->id)
            ->where(function ($query) use ($invoice) {
                $query->where('carrier_invoice_reference', $invoice)
                    ->orWhereHas('order', function ($orderQuery) use ($invoice) {
                        $orderQuery->where('increment_id', $invoice);

                        if (is_numeric($invoice)) {
                            $orderQuery->orWhere('id', (int) $invoice);
                        }
                    });
            })
            ->latest('id')
            ->first();
    }

    protected function persistSyncState(ShipmentRecord $shipmentRecord, string $status, string $message): void
    {
        $this->shipmentRecordRepository->update([
            'last_tracking_synced_at' => now()->toDateTimeString(),
            'last_tracking_sync_status' => $status,
            'last_tracking_sync_message' => $message,
        ], $shipmentRecord->id);
    }

    protected function extractFirstValue(array $payload, array $paths): ?string
    {
        foreach ($paths as $path) {
            $value = data_get($payload, $path);

            if (! is_string($value) && ! is_numeric($value)) {
                continue;
            }

            $value = trim((string) $value);

            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }
}
