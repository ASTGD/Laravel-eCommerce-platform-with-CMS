<?php

namespace Platform\CommerceCore\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Platform\CommerceCore\Models\ShipmentCarrier;
use Platform\CommerceCore\Services\SteadfastWebhookService;
use Platform\PlatformSupport\Services\SecurityAuditLogger;

class SteadfastWebhookController
{
    public function __construct(
        protected SteadfastWebhookService $steadfastWebhookService,
        protected SecurityAuditLogger $securityAuditLogger,
    ) {}

    public function handle(Request $request, ShipmentCarrier $carrier): JsonResponse
    {
        abort_unless($carrier->trackingDriver() === 'steadfast', 404);

        $configuredSecret = trim((string) $carrier->webhook_secret);

        if ($configuredSecret === '') {
            $this->securityAuditLogger->logForSubject('courier.webhook.rejected', $carrier, payload: [
                'provider' => 'steadfast',
                'reason' => 'missing_configured_secret',
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => 'invalid',
                'message' => 'Webhook secret is not configured for this carrier.',
            ], 422);
        }

        if (! $this->hasValidSecret($request, $configuredSecret)) {
            $this->securityAuditLogger->logForSubject('courier.webhook.rejected', $carrier, payload: [
                'provider' => 'steadfast',
                'reason' => 'invalid_token',
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => 'unauthorized',
                'message' => 'Invalid webhook authorization token.',
            ], 401);
        }

        $result = $this->steadfastWebhookService->handle($carrier, $request->all());

        $this->securityAuditLogger->logForSubject(
            $result->httpStatus >= 400 ? 'courier.webhook.rejected' : 'courier.webhook.accepted',
            $carrier,
            payload: [
                'provider' => 'steadfast',
                'status' => $result->status,
                'http_status' => $result->httpStatus,
                'shipment_record_id' => $result->shipmentRecordId,
                'external_status' => $result->externalStatus,
                'ip' => $request->ip(),
            ],
        );

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
