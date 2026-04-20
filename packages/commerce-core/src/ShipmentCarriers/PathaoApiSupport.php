<?php

namespace Platform\CommerceCore\ShipmentCarriers;

use Platform\CommerceCore\Models\ShipmentCarrier;
use Platform\CommerceCore\Models\ShipmentRecord;

class PathaoApiSupport
{
    public function resolveEndpoint(ShipmentCarrier $carrier, string $path): ?string
    {
        $baseUrl = trim((string) $carrier->api_base_url);

        if ($baseUrl === '') {
            return null;
        }

        return rtrim($baseUrl, '/').'/'.ltrim($path, '/');
    }

    public function authPayload(ShipmentCarrier $carrier): array
    {
        return array_filter([
            'client_id' => trim((string) $carrier->api_key) ?: null,
            'client_secret' => trim((string) $carrier->api_secret) ?: null,
            'grant_type' => 'password',
            'username' => trim((string) $carrier->api_username) ?: null,
            'password' => trim((string) $carrier->api_password) ?: null,
        ], static fn ($value) => ! is_null($value) && $value !== '');
    }

    public function locationQuery(ShipmentRecord $shipmentRecord): array
    {
        return array_filter([
            'city' => trim((string) ($shipmentRecord->destination_city ?: $shipmentRecord->destination_region)) ?: null,
            'region' => trim((string) $shipmentRecord->destination_region) ?: null,
            'address' => trim((string) $shipmentRecord->recipient_address) ?: null,
        ], static fn ($value) => ! is_null($value) && $value !== '');
    }

    public function merchantOrderId(ShipmentRecord $shipmentRecord): string
    {
        $orderIncrementId = trim((string) $shipmentRecord->order?->increment_id);

        return sprintf(
            '%s-S%s',
            $orderIncrementId !== '' ? $orderIncrementId : 'ORDER-'.$shipmentRecord->order_id,
            $shipmentRecord->id,
        );
    }

    public function senderName(ShipmentCarrier $carrier): ?string
    {
        return trim((string) $carrier->contact_name) ?: trim((string) $carrier->name) ?: null;
    }

    public function senderPhone(ShipmentCarrier $carrier): ?string
    {
        return trim((string) $carrier->contact_phone) ?: null;
    }

    public function recipientAddress(ShipmentRecord $shipmentRecord): string
    {
        $parts = [
            trim((string) $shipmentRecord->recipient_address),
            trim((string) $shipmentRecord->destination_city),
            trim((string) $shipmentRecord->destination_region),
            trim((string) $shipmentRecord->destination_country),
        ];

        $parts = array_values(array_filter($parts, static fn (string $part) => $part !== ''));

        return implode(', ', array_unique($parts));
    }
}
