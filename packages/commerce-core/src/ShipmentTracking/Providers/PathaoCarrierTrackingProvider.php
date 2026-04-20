<?php

namespace Platform\CommerceCore\ShipmentTracking\Providers;

use Illuminate\Support\Facades\Http;
use Platform\CommerceCore\Contracts\CarrierTrackingProvider;
use Platform\CommerceCore\Models\ShipmentCarrier;
use Platform\CommerceCore\Models\ShipmentRecord;
use Platform\CommerceCore\Services\ShipmentRecordService;
use Platform\CommerceCore\ShipmentCarriers\PathaoApiSupport;
use Platform\CommerceCore\ShipmentTracking\CarrierTrackingSyncResult;
use Platform\CommerceCore\ShipmentTracking\PathaoTrackingStatusMapper;
use Throwable;

class PathaoCarrierTrackingProvider implements CarrierTrackingProvider
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
        'data.data.order_status_slug',
        'data.data.order_status',
        'data.data.status_slug',
        'data.data.status',
        'tracking.order_status_slug',
        'tracking.order_status',
        'consignment.order_status_slug',
        'consignment.order_status',
        'delivery_status',
        'data.delivery_status',
        'data.data.delivery_status',
    ];

    public function __construct(
        protected ShipmentRecordService $shipmentRecordService,
        protected PathaoTrackingStatusMapper $statusMapper,
        protected PathaoApiSupport $apiSupport,
        protected string $driver = 'pathao',
        protected string $label = 'Pathao',
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
                '%s tracking sync requires a carrier API base URL and a consignment ID or tracking number.',
                $this->label,
            ));
        }

        $tokenResponse = $this->issueToken($carrier);

        if (! $tokenResponse['ok']) {
            return CarrierTrackingSyncResult::failed($tokenResponse['message']);
        }

        try {
            $response = Http::acceptJson()
                ->timeout(20)
                ->withToken($tokenResponse['token'])
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
                '%s tracking response did not include a mappable order status.',
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

    protected function issueToken(ShipmentCarrier $carrier): array
    {
        $issueTokenEndpoint = $this->apiSupport->resolveEndpoint($carrier, 'issue-token');

        if (! $issueTokenEndpoint) {
            return [
                'ok' => false,
                'message' => sprintf('%s tracking sync requires a configured API base URL.', $this->label),
            ];
        }

        try {
            $response = Http::acceptJson()
                ->timeout(20)
                ->post($issueTokenEndpoint, $this->apiSupport->authPayload($carrier));
        } catch (Throwable $exception) {
            return [
                'ok' => false,
                'message' => sprintf('%s token request failed: %s', $this->label, $exception->getMessage()),
            ];
        }

        if (! $response->successful()) {
            return [
                'ok' => false,
                'message' => sprintf('%s token request failed with HTTP %s.', $this->label, $response->status()),
            ];
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            return [
                'ok' => false,
                'message' => sprintf('%s token response did not return JSON.', $this->label),
            ];
        }

        $accessToken = $this->extractFirstValue($payload, ['access_token', 'data.access_token', 'data.token']);

        if (! $accessToken) {
            return [
                'ok' => false,
                'message' => sprintf('%s token response did not include an access token.', $this->label),
            ];
        }

        return [
            'ok' => true,
            'token' => $accessToken,
            'payload' => $payload,
        ];
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

    protected function extractFirstValue(array $payload, array $paths): ?string
    {
        foreach ($paths as $path) {
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
