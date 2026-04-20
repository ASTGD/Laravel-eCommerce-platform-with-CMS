<?php

namespace Platform\CommerceCore\ShipmentBooking;

use Carbon\CarbonInterface;

class CarrierBookingResult
{
    public function __construct(
        public readonly string $status,
        public readonly string $message,
        public readonly ?string $bookingReference = null,
        public readonly ?string $consignmentId = null,
        public readonly ?string $invoiceReference = null,
        public readonly ?string $trackingNumber = null,
        public readonly ?CarbonInterface $bookedAt = null,
        public readonly array $meta = [],
    ) {}

    public static function booked(
        string $message,
        ?string $bookingReference = null,
        ?string $consignmentId = null,
        ?string $invoiceReference = null,
        ?string $trackingNumber = null,
        ?CarbonInterface $bookedAt = null,
        array $meta = [],
    ): self {
        return new self(
            status: 'booked',
            message: $message,
            bookingReference: $bookingReference,
            consignmentId: $consignmentId,
            invoiceReference: $invoiceReference,
            trackingNumber: $trackingNumber,
            bookedAt: $bookedAt,
            meta: $meta,
        );
    }

    public static function failed(string $message, array $meta = []): self
    {
        return new self(status: 'failed', message: $message, meta: $meta);
    }

    public static function skipped(string $message, array $meta = []): self
    {
        return new self(status: 'skipped', message: $message, meta: $meta);
    }

    public function successful(): bool
    {
        return $this->status === 'booked';
    }
}
