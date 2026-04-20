<?php

namespace Platform\CommerceCore\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Platform\CommerceCore\Models\ShipmentCarrier;
use Platform\CommerceCore\Services\PathaoWebhookService;

class PathaoWebhookController
{
    public function __construct(protected PathaoWebhookService $pathaoWebhookService) {}

    public function handle(Request $request, ShipmentCarrier $carrier): JsonResponse
    {
        abort_unless($carrier->trackingDriver() === 'pathao', 404);

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

        $result = $this->pathaoWebhookService->handle($carrier, $request->all());

        return response()
            ->json([
                'status' => $result->status,
                'message' => $result->message,
                'shipment_record_id' => $result->shipmentRecordId,
                'external_status' => $result->externalStatus,
            ], $result->httpStatus)
            ->header('X-Pathao-Merchant-Webhook-Integration-Secret', $configuredSecret);
    }

    protected function hasValidSecret(Request $request, string $configuredSecret): bool
    {
        $candidates = array_filter([
            $request->header('X-PATHAO-Signature'),
            $request->header('X-Pathao-Signature'),
            $request->header('X-Pathao-Merchant-Webhook-Integration-Secret'),
            $request->bearerToken(),
        ], fn (?string $value) => is_string($value) && trim($value) !== '');

        foreach ($candidates as $candidate) {
            if (hash_equals($configuredSecret, trim($candidate))) {
                return true;
            }
        }

        return false;
    }
}
