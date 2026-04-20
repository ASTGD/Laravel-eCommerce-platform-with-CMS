<?php

namespace Platform\CommerceCore\ShipmentBooking\Providers;

use Platform\CommerceCore\Contracts\CarrierBookingProvider;
use Platform\CommerceCore\Models\ShipmentCarrier;
use Platform\CommerceCore\Models\ShipmentRecord;
use Platform\CommerceCore\ShipmentBooking\CarrierBookingResult;

class ManualCarrierBookingProvider implements CarrierBookingProvider
{
    public function __construct(
        protected string $driver = 'manual',
        protected string $label = 'Manual',
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
        return CarrierBookingResult::skipped(sprintf(
            '%s does not support automated courier booking yet.',
            $this->label,
        ));
    }
}
