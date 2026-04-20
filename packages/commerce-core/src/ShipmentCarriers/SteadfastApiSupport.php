<?php

namespace Platform\CommerceCore\ShipmentCarriers;

use Platform\CommerceCore\Models\ShipmentCarrier;
use Platform\CommerceCore\Models\ShipmentRecord;

class SteadfastApiSupport
{
    public function resolveTrackingEndpoint(ShipmentCarrier $carrier, ShipmentRecord $shipmentRecord): ?string
    {
        $template = trim((string) $carrier->api_base_url);
        $trackingNumber = trim((string) $shipmentRecord->tracking_number);

        if ($template === '' || $trackingNumber === '') {
            return null;
        }

        if (! str_contains($template, '{tracking_number}') && ! str_contains($template, '{tracking}')) {
            $template = rtrim($this->resolveBaseUrl($template), '/').'/status_by_trackingcode/{tracking_number}';
        }

        return str_replace(
            ['{tracking_number}', '{tracking}'],
            [rawurlencode($trackingNumber), rawurlencode($trackingNumber)],
            $template,
        );
    }

    public function resolveBookingEndpoint(ShipmentCarrier $carrier): ?string
    {
        $template = trim((string) $carrier->api_base_url);

        if ($template === '') {
            return null;
        }

        return rtrim($this->resolveBaseUrl($template), '/').'/create_order';
    }

    public function headers(ShipmentCarrier $carrier): array
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

    protected function resolveBaseUrl(string $template): string
    {
        $baseUrl = preg_replace('#/(status_by_trackingcode|tracking-status|track)(/.*)?$#i', '', $template);
        $baseUrl = preg_replace('#/\{tracking_number\}$#i', '', $baseUrl);
        $baseUrl = preg_replace('#/\{tracking\}$#i', '', $baseUrl);

        return rtrim($baseUrl ?: $template, '/');
    }
}
