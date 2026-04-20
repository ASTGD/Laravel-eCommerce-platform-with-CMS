<?php

namespace Platform\CommerceCore\Contracts;

use Platform\CommerceCore\Models\ShipmentCarrier;
use Platform\CommerceCore\Models\ShipmentRecord;
use Platform\CommerceCore\ShipmentBooking\CarrierBookingResult;

interface CarrierBookingProvider
{
    public function driver(): string;

    public function label(): string;

    public function createBooking(ShipmentCarrier $carrier, ShipmentRecord $shipmentRecord, array $context = []): CarrierBookingResult;
}
