<?php

namespace Platform\CommerceCore\Listeners;

use Platform\CommerceCore\Services\ShipmentRecordService;
use Webkul\Sales\Contracts\Shipment as ShipmentContract;

class SyncShipmentRecordFromNativeShipment
{
    public function __construct(protected ShipmentRecordService $shipmentRecordService) {}

    public function handle(ShipmentContract $shipment): void
    {
        $this->shipmentRecordService->syncFromNativeShipment(
            $shipment,
            (array) request()->input('shipment', [])
        );
    }
}
