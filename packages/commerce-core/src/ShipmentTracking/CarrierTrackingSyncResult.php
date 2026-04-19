<?php

namespace Platform\CommerceCore\ShipmentTracking;

class CarrierTrackingSyncResult
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SKIPPED = 'skipped';
    public const STATUS_SYNCED = 'synced';
    public const STATUS_FAILED = 'failed';

    public function __construct(
        public readonly string $status,
        public readonly string $message,
        public readonly ?string $externalStatus = null,
        public readonly int $eventCount = 0,
    ) {}

    public static function pending(string $message, ?string $externalStatus = null): self
    {
        return new self(self::STATUS_PENDING, $message, $externalStatus);
    }

    public static function skipped(string $message): self
    {
        return new self(self::STATUS_SKIPPED, $message);
    }

    public static function synced(string $message, ?string $externalStatus = null, int $eventCount = 0): self
    {
        return new self(self::STATUS_SYNCED, $message, $externalStatus, $eventCount);
    }

    public static function failed(string $message): self
    {
        return new self(self::STATUS_FAILED, $message);
    }
}
