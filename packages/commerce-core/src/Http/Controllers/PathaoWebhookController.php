<?php

namespace Platform\CommerceCore\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Platform\CommerceCore\Models\ShipmentCarrier;
use Platform\CommerceCore\Services\PathaoWebhookService;
use Platform\PlatformSupport\Services\SecurityAuditLogger;

class PathaoWebhookController
{
    public function __construct(
        protected PathaoWebhookService $pathaoWebhookService,
        protected SecurityAuditLogger $securityAuditLogger,
    ) {}

    public function handle(Request $request, ShipmentCarrier $carrier): JsonResponse
    {
        abort_unless($carrier->trackingDriver() === 'pathao', 404);

        $configuredSecret = trim((string) $carrier->webhook_secret);

        if ($configuredSecret === '') {
            $this->securityAuditLogger->logForSubject('courier.webhook.rejected', $carrier, payload: [
                'provider' => 'pathao',
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
                'provider' => 'pathao',
                'reason' => 'invalid_token',
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => 'unauthorized',
                'message' => 'Invalid webhook authorization token.',
            ], 401);
        }

        $result = $this->pathaoWebhookService->handle($carrier, $request->all());

        $this->securityAuditLogger->logForSubject(
            $result->httpStatus >= 400 ? 'courier.webhook.rejected' : 'courier.webhook.accepted',
            $carrier,
            payload: [
                'provider' => 'pathao',
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
