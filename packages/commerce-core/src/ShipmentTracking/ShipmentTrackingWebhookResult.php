<?php

namespace Platform\CommerceCore\ShipmentTracking;

class ShipmentTrackingWebhookResult
{
    public function __construct(
        public readonly string $status,
        public readonly string $message,
        public readonly int $httpStatus,
        public readonly ?int $shipmentRecordId = null,
        public readonly ?string $externalStatus = null,
    ) {}

    public static function ok(string $message, ?int $shipmentRecordId = null, ?string $externalStatus = null): self
    {
        return new self('ok', $message, 200, $shipmentRecordId, $externalStatus);
    }

    public static function accepted(string $message, ?int $shipmentRecordId = null, ?string $externalStatus = null): self
    {
        return new self('accepted', $message, 202, $shipmentRecordId, $externalStatus);
    }

    public static function invalid(string $message): self
    {
        return new self('invalid', $message, 422);
    }
}
