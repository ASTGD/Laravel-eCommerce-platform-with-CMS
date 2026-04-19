<?php

namespace Platform\CommerceCore\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Platform\CommerceCore\Models\ShipmentRecord;
use Platform\CommerceCore\Services\CarrierTrackingSyncService;

class SyncShipmentTrackingJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly int $shipmentRecordId) {}

    public function handle(CarrierTrackingSyncService $carrierTrackingSyncService): void
    {
        $shipmentRecord = ShipmentRecord::query()->find($this->shipmentRecordId);

        if (! $shipmentRecord) {
            return;
        }

        $carrierTrackingSyncService->syncShipmentRecord($shipmentRecord);
    }
}
