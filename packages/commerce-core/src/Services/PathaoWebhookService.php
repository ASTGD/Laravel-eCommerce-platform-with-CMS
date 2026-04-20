<?php

namespace Platform\CommerceCore\Services;

use Platform\CommerceCore\Models\ShipmentCarrier;
use Platform\CommerceCore\Models\ShipmentRecord;
use Platform\CommerceCore\Repositories\ShipmentRecordRepository;
use Platform\CommerceCore\ShipmentTracking\CarrierTrackingSyncResult;
use Platform\CommerceCore\ShipmentTracking\PathaoTrackingStatusMapper;
use Platform\CommerceCore\ShipmentTracking\ShipmentTrackingWebhookResult;

class PathaoWebhookService
{
    protected const STATUS_PATHS = [
        'order_status_slug',
        'order_status',
        'status_slug',
        'status',
        'data.order_status_slug',
        'data.order_status',
        'data.status_slug',
        'data.status',
        'tracking.order_status_slug',
        'tracking.order_status',
        'consignment.order_status_slug',
        'consignment.order_status',
        'delivery_status',
        'data.delivery_status',
        'consignment.delivery_status',
    ];

    protected const CONSIGNMENT_ID_PATHS = [
        'consignment_id',
        'tracking_number',
        'tracking_code',
        'data.consignment_id',
        'data.tracking_number',
        'data.tracking_code',
        'consignment.consignment_id',
        'parcel.consignment_id',
    ];

    protected const INVOICE_PATHS = [
        'invoice_id',
        'invoice',
        'merchant_order_id',
        'order_reference',
        'data.invoice_id',
        'data.invoice',
        'data.merchant_order_id',
        'consignment.invoice',
    ];

    public function __construct(
        protected ShipmentRecordService $shipmentRecordService,
        protected ShipmentRecordRepository $shipmentRecordRepository,
        protected PathaoTrackingStatusMapper $statusMapper,
    ) {}

    public function handle(ShipmentCarrier $carrier, array $payload): ShipmentTrackingWebhookResult
    {
        $externalStatus = $this->extractFirstValue($payload, self::STATUS_PATHS);

        if (! $externalStatus) {
            return ShipmentTrackingWebhookResult::invalid('Webhook payload is missing an order status.');
        }

        $consignmentId = $this->extractFirstValue($payload, self::CONSIGNMENT_ID_PATHS);
        $invoice = $this->extractFirstValue($payload, self::INVOICE_PATHS);

        if (! $consignmentId && ! $invoice) {
            return ShipmentTrackingWebhookResult::invalid('Webhook payload must include a consignment id or invoice reference.');
        }

        $shipmentRecord = $this->resolveShipmentRecord($carrier, $consignmentId, $invoice);

        if (! $shipmentRecord) {
            return ShipmentTrackingWebhookResult::accepted(
                'Webhook received but no matching local shipment record was found.',
                externalStatus: $externalStatus,
            );
        }

        $mappedStatus = $this->statusMapper->map($externalStatus);

        if (! $mappedStatus) {
            $message = sprintf(
                'Pathao webhook received external status "%s", but no local status mapping exists yet.',
                $externalStatus,
            );

            $this->persistSyncState($shipmentRecord, CarrierTrackingSyncResult::STATUS_PENDING, $message);

            return ShipmentTrackingWebhookResult::accepted($message, $shipmentRecord->id, $externalStatus);
        }

        if ($mappedStatus === $shipmentRecord->status) {
            $message = sprintf(
                'Pathao webhook external status "%s" already matches "%s".',
                $externalStatus,
                $shipmentRecord->status_label,
            );

            $this->persistSyncState($shipmentRecord, CarrierTrackingSyncResult::STATUS_SYNCED, $message);

            return ShipmentTrackingWebhookResult::ok($message, $shipmentRecord->id, $externalStatus);
        }

        if ($this->statusMapper->wouldDowngrade($shipmentRecord->status, $mappedStatus)) {
            $message = sprintf(
                'Pathao webhook external status "%s" maps to "%s", but the current shipment status "%s" was kept to avoid a downgrade.',
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
                'Pathao webhook mapped external status "%s" to "%s".',
                $externalStatus,
                ShipmentRecord::statusLabels()[$mappedStatus] ?? $mappedStatus,
            ),
            null,
        );

        $message = sprintf(
            'Pathao webhook synced external status "%s" to "%s".',
            $externalStatus,
            ShipmentRecord::statusLabels()[$mappedStatus] ?? $mappedStatus,
        );

        $this->persistSyncState($shipmentRecord, CarrierTrackingSyncResult::STATUS_SYNCED, $message);

        return ShipmentTrackingWebhookResult::ok($message, $shipmentRecord->id, $externalStatus);
    }

    protected function resolveShipmentRecord(
        ShipmentCarrier $carrier,
        ?string $consignmentId,
        ?string $invoice,
    ): ?ShipmentRecord
    {
        if ($consignmentId) {
            $shipmentRecord = ShipmentRecord::query()
                ->where('shipment_carrier_id', $carrier->id)
                ->where(function ($query) use ($consignmentId) {
                    $query->where('carrier_consignment_id', $consignmentId)
                        ->orWhere('tracking_number', $consignmentId);
                })
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
