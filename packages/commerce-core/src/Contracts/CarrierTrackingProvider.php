<?php

namespace Platform\CommerceCore\Contracts;

use Platform\CommerceCore\Models\ShipmentCarrier;
use Platform\CommerceCore\Models\ShipmentRecord;
use Platform\CommerceCore\ShipmentTracking\CarrierTrackingSyncResult;

interface CarrierTrackingProvider
{
    public function driver(): string;

    public function label(): string;

    public function fetchTracking(ShipmentCarrier $carrier, ShipmentRecord $shipmentRecord): CarrierTrackingSyncResult;
}
