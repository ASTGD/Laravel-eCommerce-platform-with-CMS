<?php

namespace Platform\CommerceCore\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Platform\CommerceCore\Models\ShipmentCarrier;
use Platform\CommerceCore\Services\SteadfastWebhookService;

class SteadfastWebhookController
{
    public function __construct(protected SteadfastWebhookService $steadfastWebhookService) {}

    public function handle(Request $request, ShipmentCarrier $carrier): JsonResponse
    {
        abort_unless($carrier->trackingDriver() === 'steadfast', 404);

        $configuredSecret = trim((string) $carrier->webhook_secret);

        if ($configuredSecret === '') {
            return response()->json([
                'status' => 'invalid',
                'message' => 'Webhook secret is not configured for this carrier.',
            ], 422);
        }

        if (! $this->hasValidSecret($request, $configuredSecret)) {
            return response()->json([
                'status' => 'unauthorized',
                'message' => 'Invalid webhook authorization token.',
            ], 401);
        }

        $result = $this->steadfastWebhookService->handle($carrier, $request->all());

        return response()->json([
            'status' => $result->status,
            'message' => $result->message,
            'shipment_record_id' => $result->shipmentRecordId,
            'external_status' => $result->externalStatus,
        ], $result->httpStatus);
    }

    protected function hasValidSecret(Request $request, string $configuredSecret): bool
    {
        $candidates = array_filter([
            $request->bearerToken(),
            $request->header('X-Webhook-Secret'),
            $request->header('X-Webhook-Token'),
            $request->header('X-Steadfast-Token'),
        ], fn (?string $value) => is_string($value) && trim($value) !== '');

        foreach ($candidates as $candidate) {
            if (hash_equals($configuredSecret, trim($candidate))) {
                return true;
            }
        }

        return false;
    }
}
