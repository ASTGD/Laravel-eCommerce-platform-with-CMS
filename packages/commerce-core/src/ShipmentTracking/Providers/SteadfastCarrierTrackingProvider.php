<?php

namespace Platform\CommerceCore\ShipmentTracking\Providers;

use Illuminate\Support\Facades\Http;
use Platform\CommerceCore\Contracts\CarrierTrackingProvider;
use Platform\CommerceCore\Models\ShipmentCarrier;
use Platform\CommerceCore\Models\ShipmentRecord;
use Platform\CommerceCore\Services\ShipmentRecordService;
use Platform\CommerceCore\ShipmentTracking\CarrierTrackingSyncResult;
use Throwable;

class SteadfastCarrierTrackingProvider implements CarrierTrackingProvider
{
    /**
     * Statuses exposed by Steadfast are more coarse than the internal shipment
     * model, so early-state mappings are intentionally conservative.
     */
    protected const STATUS_MAP = [
        'created' => ShipmentRecord::STATUS_READY_FOR_PICKUP,
        'booked' => ShipmentRecord::STATUS_READY_FOR_PICKUP,
        'ready_for_pickup' => ShipmentRecord::STATUS_READY_FOR_PICKUP,
        'in_review' => ShipmentRecord::STATUS_READY_FOR_PICKUP,
        'picked_up' => ShipmentRecord::STATUS_HANDED_TO_CARRIER,
        'picked' => ShipmentRecord::STATUS_HANDED_TO_CARRIER,
        'handed_to_carrier' => ShipmentRecord::STATUS_HANDED_TO_CARRIER,
        'received_by_carrier' => ShipmentRecord::STATUS_HANDED_TO_CARRIER,
        'pending' => ShipmentRecord::STATUS_HANDED_TO_CARRIER,
        'hold' => ShipmentRecord::STATUS_HANDED_TO_CARRIER,
        'in_transit' => ShipmentRecord::STATUS_IN_TRANSIT,
        'transit' => ShipmentRecord::STATUS_IN_TRANSIT,
        'at_hub' => ShipmentRecord::STATUS_IN_TRANSIT,
        'arrived_at_hub' => ShipmentRecord::STATUS_IN_TRANSIT,
        'on_the_way' => ShipmentRecord::STATUS_IN_TRANSIT,
        'out_for_delivery' => ShipmentRecord::STATUS_OUT_FOR_DELIVERY,
        'delivery_ongoing' => ShipmentRecord::STATUS_OUT_FOR_DELIVERY,
        'on_delivery' => ShipmentRecord::STATUS_OUT_FOR_DELIVERY,
        'partial_delivered_approval_pending' => ShipmentRecord::STATUS_OUT_FOR_DELIVERY,
        'partial_delivered' => ShipmentRecord::STATUS_OUT_FOR_DELIVERY,
        'delivered_approval_pending' => ShipmentRecord::STATUS_DELIVERED,
        'delivered' => ShipmentRecord::STATUS_DELIVERED,
        'delivery_failed' => ShipmentRecord::STATUS_DELIVERY_FAILED,
        'failed' => ShipmentRecord::STATUS_DELIVERY_FAILED,
        'undelivered' => ShipmentRecord::STATUS_DELIVERY_FAILED,
        'attempted' => ShipmentRecord::STATUS_DELIVERY_FAILED,
        'returned' => ShipmentRecord::STATUS_RETURNED,
        'return_completed' => ShipmentRecord::STATUS_RETURNED,
        'rto' => ShipmentRecord::STATUS_RETURNED,
        'returned_to_origin' => ShipmentRecord::STATUS_RETURNED,
        'cancelled_approval_pending' => ShipmentRecord::STATUS_CANCELED,
        'cancelled' => ShipmentRecord::STATUS_CANCELED,
        'canceled' => ShipmentRecord::STATUS_CANCELED,
    ];

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

    protected const STATUS_RANKS = [
        ShipmentRecord::STATUS_DRAFT => 0,
        ShipmentRecord::STATUS_READY_FOR_PICKUP => 1,
        ShipmentRecord::STATUS_HANDED_TO_CARRIER => 2,
        ShipmentRecord::STATUS_IN_TRANSIT => 3,
        ShipmentRecord::STATUS_OUT_FOR_DELIVERY => 4,
        ShipmentRecord::STATUS_DELIVERED => 5,
        ShipmentRecord::STATUS_DELIVERY_FAILED => 5,
        ShipmentRecord::STATUS_RETURNED => 5,
        ShipmentRecord::STATUS_CANCELED => 5,
    ];

    public function __construct(
        protected ShipmentRecordService $shipmentRecordService,
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
        $endpoint = $this->resolveEndpoint($carrier, $shipmentRecord);

        if (! $endpoint) {
            return CarrierTrackingSyncResult::failed(sprintf(
                '%s tracking sync requires a carrier API base URL or endpoint template.',
                $this->label,
            ));
        }

        try {
            $response = Http::acceptJson()
                ->timeout(20)
                ->withHeaders($this->headers($carrier))
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

        $mappedStatus = $this->mapExternalStatus($externalStatus);

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

        if ($this->wouldDowngrade($shipmentRecord->status, $mappedStatus)) {
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

    protected function resolveEndpoint(ShipmentCarrier $carrier, ShipmentRecord $shipmentRecord): ?string
    {
        $template = trim((string) $carrier->api_base_url);
        $trackingNumber = trim((string) $shipmentRecord->tracking_number);

        if ($template === '' || $trackingNumber === '') {
            return null;
        }

        if (! str_contains($template, '{tracking_number}') && ! str_contains($template, '{tracking}')) {
            $template = rtrim($template, '/').'/status_by_trackingcode/{tracking_number}';
        }

        return str_replace(
            ['{tracking_number}', '{tracking}'],
            [rawurlencode($trackingNumber), rawurlencode($trackingNumber)],
            $template,
        );
    }

    protected function headers(ShipmentCarrier $carrier): array
    {
        $headers = [];

        if ($apiKey = trim((string) $carrier->api_key)) {
            $headers['Api-Key'] = $apiKey;
            $headers['API-KEY'] = $apiKey;
            $headers['X-API-KEY'] = $apiKey;
        }

        if ($apiSecret = trim((string) $carrier->api_secret)) {
            $headers['Secret-Key'] = $apiSecret;
            $headers['SECRET-KEY'] = $apiSecret;
            $headers['X-API-SECRET'] = $apiSecret;
        }

        if ($username = trim((string) $carrier->api_username)) {
            $headers['X-API-USERNAME'] = $username;
        }

        if ($password = trim((string) $carrier->api_password)) {
            $headers['X-API-PASSWORD'] = $password;
        }

        return $headers;
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

    protected function mapExternalStatus(string $externalStatus): ?string
    {
        return self::STATUS_MAP[$this->normalizeStatus($externalStatus)] ?? null;
    }

    protected function wouldDowngrade(string $currentStatus, string $mappedStatus): bool
    {
        return (self::STATUS_RANKS[$mappedStatus] ?? -1) < (self::STATUS_RANKS[$currentStatus] ?? -1);
    }

    protected function normalizeStatus(string $status): string
    {
        $normalized = mb_strtolower(trim($status));
        $normalized = preg_replace('/[^a-z0-9]+/u', '_', $normalized) ?: $normalized;

        return trim($normalized, '_');
    }
}
