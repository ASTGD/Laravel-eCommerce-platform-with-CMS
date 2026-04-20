<?php

namespace Platform\CommerceCore\ShipmentTracking\Providers;

use Illuminate\Support\Facades\Http;
use Platform\CommerceCore\Contracts\CarrierTrackingProvider;
use Platform\CommerceCore\Models\ShipmentCarrier;
use Platform\CommerceCore\Models\ShipmentRecord;
use Platform\CommerceCore\ShipmentCarriers\SteadfastApiSupport;
use Platform\CommerceCore\Services\ShipmentRecordService;
use Platform\CommerceCore\ShipmentTracking\CarrierTrackingSyncResult;
use Platform\CommerceCore\ShipmentTracking\SteadfastTrackingStatusMapper;
use Throwable;

class SteadfastCarrierTrackingProvider implements CarrierTrackingProvider
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

    public function __construct(
        protected ShipmentRecordService $shipmentRecordService,
        protected SteadfastTrackingStatusMapper $statusMapper,
        protected SteadfastApiSupport $apiSupport,
        protected string $driver = 'steadfast',
        protected string $label = 'Steadfast',
    ) {}

    public function driver(): string
    {
        return $this->driver;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function fetchTracking(ShipmentCarrier $carrier, ShipmentRecord $shipmentRecord): CarrierTrackingSyncResult
    {
        $endpoint = $this->apiSupport->resolveTrackingEndpoint($carrier, $shipmentRecord);

        if (! $endpoint) {
            return CarrierTrackingSyncResult::failed(sprintf(
                '%s tracking sync requires a carrier API base URL or endpoint template.',
                $this->label,
            ));
        }

        try {
            $response = Http::acceptJson()
                ->timeout(20)
                ->withHeaders($this->apiSupport->headers($carrier))
                ->get($endpoint);
        } catch (Throwable $exception) {
            return CarrierTrackingSyncResult::failed(sprintf(
                '%s tracking request failed: %s',
                $this->label,
                $exception->getMessage(),
            ));
        }

        if (! $response->successful()) {
            return CarrierTrackingSyncResult::failed(sprintf(
                '%s tracking request failed with HTTP %s.',
                $this->label,
                $response->status(),
            ));
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            return CarrierTrackingSyncResult::failed(sprintf(
                '%s tracking response did not return a JSON object.',
                $this->label,
            ));
        }

        $externalStatus = $this->extractExternalStatus($payload);

        if (! $externalStatus) {
            return CarrierTrackingSyncResult::pending(sprintf(
                '%s tracking response did not include a mappable delivery status.',
                $this->label,
            ));
        }

        $mappedStatus = $this->statusMapper->map($externalStatus);

        if (! $mappedStatus) {
            return CarrierTrackingSyncResult::pending(sprintf(
                '%s tracking returned external status "%s", but no local mapping exists yet.',
                $this->label,
                $externalStatus,
            ), $externalStatus);
        }

        if ($mappedStatus === $shipmentRecord->status) {
            return CarrierTrackingSyncResult::synced(sprintf(
                '%s tracking synced. External status "%s" already matches "%s".',
                $this->label,
                $externalStatus,
                $shipmentRecord->status_label,
            ), $externalStatus);
        }

        if ($this->statusMapper->wouldDowngrade($shipmentRecord->status, $mappedStatus)) {
            return CarrierTrackingSyncResult::synced(sprintf(
                '%s tracking synced. External status "%s" maps to "%s", but the current shipment status "%s" was kept to avoid a downgrade.',
                $this->label,
                $externalStatus,
                ShipmentRecord::statusLabels()[$mappedStatus] ?? $mappedStatus,
                $shipmentRecord->status_label,
            ), $externalStatus);
        }

        $this->shipmentRecordService->updateStatus(
            $shipmentRecord,
            $mappedStatus,
            sprintf(
                '%s tracking sync mapped external status "%s" to "%s".',
                $this->label,
                $externalStatus,
                ShipmentRecord::statusLabels()[$mappedStatus] ?? $mappedStatus
            ),
            null,
        );

        return CarrierTrackingSyncResult::synced(sprintf(
            '%s tracking synced. External status "%s" mapped to "%s".',
            $this->label,
            $externalStatus,
            ShipmentRecord::statusLabels()[$mappedStatus] ?? $mappedStatus,
        ), $externalStatus, 1);
    }

    protected function extractExternalStatus(array $payload): ?string
    {
        foreach (self::STATUS_PATHS as $path) {
            $value = data_get($payload, $path);

            if (is_string($value) || is_numeric($value)) {
                $value = trim((string) $value);

                if ($value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }

}
