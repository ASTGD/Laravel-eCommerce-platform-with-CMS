<?php

namespace Platform\CommerceCore\ShipmentBooking\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Platform\CommerceCore\Contracts\CarrierBookingProvider;
use Platform\CommerceCore\Models\ShipmentCarrier;
use Platform\CommerceCore\Models\ShipmentRecord;
use Platform\CommerceCore\ShipmentBooking\CarrierBookingResult;
use Platform\CommerceCore\ShipmentCarriers\PathaoApiSupport;
use Throwable;

class PathaoCarrierBookingProvider implements CarrierBookingProvider
{
    protected const CITY_PATH = 'city-list';
    protected const ZONE_PATH_TEMPLATE = 'cities/%d/zone-list';
    protected const AREA_PATH_TEMPLATE = 'zones/%d/area-list';
    protected const TOKEN_PATH = 'issue-token';
    protected const ORDER_PATH = 'orders';

    public function __construct(
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

    public function createBooking(ShipmentCarrier $carrier, ShipmentRecord $shipmentRecord, array $context = []): CarrierBookingResult
    {
        $storeId = (int) ($carrier->api_store_id ?? 0);

        if ($storeId <= 0) {
            return CarrierBookingResult::failed('Pathao booking requires a merchant store ID on the carrier.');
        }

        $issueTokenEndpoint = $this->apiSupport->resolveEndpoint($carrier, self::TOKEN_PATH);
        $orderEndpoint = $this->apiSupport->resolveEndpoint($carrier, self::ORDER_PATH);
        $cityEndpoint = $this->apiSupport->resolveEndpoint($carrier, self::CITY_PATH);

        if (! $issueTokenEndpoint || ! $orderEndpoint || ! $cityEndpoint) {
            return CarrierBookingResult::failed('Pathao booking requires a configured API base URL.');
        }

        $tokenResponse = $this->issueToken($carrier, $issueTokenEndpoint);

        if (! $tokenResponse['ok']) {
            return CarrierBookingResult::failed($tokenResponse['message']);
        }

        $accessToken = $tokenResponse['token'];
        $cityId = $this->resolveCityId($carrier, $accessToken, $cityEndpoint, $shipmentRecord);

        if (! $cityId) {
            return CarrierBookingResult::failed(sprintf(
                'Pathao booking could not resolve a district/city match for "%s".',
                $shipmentRecord->destination_city ?: $shipmentRecord->destination_region ?: 'unknown location',
            ));
        }

        $zoneId = $this->resolveZoneId(
            $carrier,
            $accessToken,
            $this->apiSupport->resolveEndpoint($carrier, sprintf(self::ZONE_PATH_TEMPLATE, $cityId)),
            $cityId,
        );
        $areaId = $zoneId
            ? $this->resolveAreaId(
                $carrier,
                $accessToken,
                $this->apiSupport->resolveEndpoint($carrier, sprintf(self::AREA_PATH_TEMPLATE, $zoneId)),
                $zoneId,
            )
            : null;

        if (! $zoneId || ! $areaId) {
            return CarrierBookingResult::failed('Pathao booking could not resolve zone and area identifiers from the merchant API.');
        }

        $merchantOrderId = $this->apiSupport->merchantOrderId($shipmentRecord);
        $payload = $this->orderPayload($carrier, $shipmentRecord, $storeId, $merchantOrderId, $cityId, $zoneId, $areaId, $context);

        try {
            $response = Http::acceptJson()
                ->timeout(20)
                ->withToken($accessToken)
                ->post($orderEndpoint, $payload);
        } catch (Throwable $exception) {
            return CarrierBookingResult::failed(sprintf(
                '%s booking request failed: %s',
                $this->label,
                $exception->getMessage(),
            ));
        }

        if (! $response->successful()) {
            return CarrierBookingResult::failed(sprintf(
                '%s booking request failed with HTTP %s.',
                $this->label,
                $response->status(),
            ));
        }

        $responsePayload = $response->json();

        if (! is_array($responsePayload)) {
            return CarrierBookingResult::failed(sprintf(
                '%s booking response did not return a JSON object.',
                $this->label,
            ));
        }

        $consignmentId = $this->extractFirstValue($responsePayload, [
            'data.consignment_id',
            'consignment_id',
            'data.consignment.consignment_id',
            'consignment.consignment_id',
        ]);
        $trackingNumber = $this->extractFirstValue($responsePayload, [
            'data.tracking_code',
            'tracking_code',
            'data.tracking_number',
            'tracking_number',
        ]) ?: $consignmentId;
        $bookingReference = $this->extractFirstValue($responsePayload, [
            'data.order_id',
            'order_id',
            'data.id',
            'id',
        ]) ?: $merchantOrderId;
        $bookedAt = $this->parseBookedAt($this->extractFirstValue($responsePayload, [
            'data.created_at',
            'created_at',
            'data.consignment.created_at',
            'consignment.created_at',
        ]));
        $message = trim((string) data_get($responsePayload, 'message', ''));

        if (! $consignmentId) {
            return CarrierBookingResult::failed(sprintf(
                '%s booking response did not include a consignment identifier.%s',
                $this->label,
                $message !== '' ? ' '.$message : '',
            ));
        }

        return CarrierBookingResult::booked(
            message: sprintf(
                '%s booking created successfully for merchant order "%s".',
                $this->label,
                $merchantOrderId,
            ),
            bookingReference: $bookingReference,
            consignmentId: $consignmentId,
            invoiceReference: $merchantOrderId,
            trackingNumber: $trackingNumber,
            bookedAt: $bookedAt ?? now(),
            meta: [
                'external_message' => $message,
                'request_payload' => $payload,
                'response_payload' => $responsePayload,
                'city_id' => $cityId,
                'zone_id' => $zoneId,
                'area_id' => $areaId,
                'merchant_order_id' => $merchantOrderId,
            ],
        );
    }

    protected function issueToken(ShipmentCarrier $carrier, string $issueTokenEndpoint): array
    {
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

        $accessToken = $this->extractFirstValue($payload, ['access_token', 'data.access_token']);

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

    protected function resolveCityId(ShipmentCarrier $carrier, string $accessToken, string $cityEndpoint, ShipmentRecord $shipmentRecord): ?int
    {
        $response = Http::acceptJson()
            ->timeout(20)
            ->withToken($accessToken)
            ->get($cityEndpoint);

        if (! $response->successful()) {
            return null;
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            return null;
        }

        $cityName = $this->cityNameCandidates($shipmentRecord);

        return $this->firstMatchingId(data_get($payload, 'data', []), $cityName);
    }

    protected function resolveZoneId(ShipmentCarrier $carrier, string $accessToken, ?string $zoneEndpoint, int $cityId): ?int
    {
        if (! $zoneEndpoint) {
            return null;
        }

        $response = Http::acceptJson()
            ->timeout(20)
            ->withToken($accessToken)
            ->get($zoneEndpoint);

        if (! $response->successful()) {
            return null;
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            return null;
        }

        return $this->firstNumericId(data_get($payload, 'data.0.id'))
            ?: $this->firstNumericId(data_get($payload, 'data.0.zone_id'))
            ?: $this->firstNumericId(data_get($payload, 'data.0.zoneId'));
    }

    protected function resolveAreaId(ShipmentCarrier $carrier, string $accessToken, ?string $areaEndpoint, int $zoneId): ?int
    {
        if (! $areaEndpoint) {
            return null;
        }

        $response = Http::acceptJson()
            ->timeout(20)
            ->withToken($accessToken)
            ->get($areaEndpoint);

        if (! $response->successful()) {
            return null;
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            return null;
        }

        return $this->firstNumericId(data_get($payload, 'data.0.id'))
            ?: $this->firstNumericId(data_get($payload, 'data.0.area_id'))
            ?: $this->firstNumericId(data_get($payload, 'data.0.areaId'));
    }

    protected function orderPayload(
        ShipmentCarrier $carrier,
        ShipmentRecord $shipmentRecord,
        int $storeId,
        string $merchantOrderId,
        int $cityId,
        int $zoneId,
        int $areaId,
        array $context = [],
    ): array {
        $payload = [
            'store_id' => $storeId,
            'merchant_order_id' => $merchantOrderId,
            'sender_name' => $this->apiSupport->senderName($carrier),
            'sender_phone' => $this->apiSupport->senderPhone($carrier),
            'recipient_name' => trim((string) $shipmentRecord->recipient_name),
            'recipient_phone' => trim((string) $shipmentRecord->recipient_phone),
            'recipient_address' => $this->apiSupport->recipientAddress($shipmentRecord),
            'recipient_city' => $cityId,
            'recipient_zone' => $zoneId,
            'recipient_area' => $areaId,
            'delivery_type' => 48,
            'item_type' => 2,
            'item_quantity' => 1,
            'item_weight' => (float) ($context['item_weight'] ?? 0.5),
            'amount_to_collect' => (float) ($shipmentRecord->cod_amount_expected ?? 0),
            'item_description' => $context['item_description'] ?? sprintf('Order #%s', $shipmentRecord->order?->increment_id ?? $shipmentRecord->order_id),
        ];

        if (! blank($context['note'] ?? null)) {
            $payload['special_instruction'] = trim((string) $context['note']);
        }

        return array_filter($payload, static fn ($value) => ! is_null($value) && $value !== '');
    }

    protected function cityNameCandidates(ShipmentRecord $shipmentRecord): array
    {
        return array_values(array_filter([
            trim((string) $shipmentRecord->destination_city),
            trim((string) $shipmentRecord->destination_region),
        ]));
    }

    protected function firstMatchingId(array $items, array $candidates): ?int
    {
        foreach ($items as $item) {
            $name = strtolower(trim((string) data_get($item, 'name', data_get($item, 'city_name', data_get($item, 'zone_name', data_get($item, 'area_name'))))));

            if ($name === '') {
                continue;
            }

            foreach ($candidates as $candidate) {
                if (strtolower(trim($candidate)) === $name) {
                    return $this->firstNumericId(data_get($item, 'id'))
                        ?: $this->firstNumericId(data_get($item, 'city_id'))
                        ?: $this->firstNumericId(data_get($item, 'zone_id'))
                        ?: $this->firstNumericId(data_get($item, 'area_id'));
                }
            }
        }

        $first = $items[0] ?? null;

        return $this->firstNumericId(data_get($first, 'id'))
            ?: $this->firstNumericId(data_get($first, 'city_id'))
            ?: $this->firstNumericId(data_get($first, 'zone_id'))
            ?: $this->firstNumericId(data_get($first, 'area_id'));
    }

    protected function firstNumericId(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
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

    protected function parseBookedAt(?string $value): ?CarbonImmutable
    {
        if (! $value) {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (Throwable) {
            return null;
        }
    }
}
