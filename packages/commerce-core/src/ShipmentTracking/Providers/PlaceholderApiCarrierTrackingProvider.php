<?php

namespace Platform\CommerceCore\ShipmentTracking\Providers;

use Platform\CommerceCore\Contracts\CarrierTrackingProvider;
use Platform\CommerceCore\Models\ShipmentCarrier;
use Platform\CommerceCore\Models\ShipmentRecord;
use Platform\CommerceCore\ShipmentTracking\CarrierTrackingSyncResult;

class PlaceholderApiCarrierTrackingProvider implements CarrierTrackingProvider
{
    public function __construct(
        protected string $driver,
        protected string $label,
    ) {}

    public function driver(): string
    {
        return $this->driver;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function fetchTracking(ShipmentCarrier $carrier, ShipmentRecord $shipmentRecord): CarrierTrackingSyncResult
    {
        return CarrierTrackingSyncResult::pending(sprintf(
            '%s tracking sync foundation is configured, but live API import is not implemented yet.',
            $this->label,
        ));
    }
}
