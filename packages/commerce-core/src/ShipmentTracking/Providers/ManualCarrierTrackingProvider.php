<?php

namespace Platform\CommerceCore\ShipmentTracking\Providers;

use Platform\CommerceCore\Contracts\CarrierTrackingProvider;
use Platform\CommerceCore\Models\ShipmentCarrier;
use Platform\CommerceCore\Models\ShipmentRecord;
use Platform\CommerceCore\ShipmentTracking\CarrierTrackingSyncResult;

class ManualCarrierTrackingProvider implements CarrierTrackingProvider
{
    public function __construct(
        protected string $driver = ShipmentCarrier::INTEGRATION_DRIVER_MANUAL,
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

    public function fetchTracking(ShipmentCarrier $carrier, ShipmentRecord $shipmentRecord): CarrierTrackingSyncResult
    {
        return CarrierTrackingSyncResult::skipped('Manual carrier tracking does not use API sync.');
    }
}
