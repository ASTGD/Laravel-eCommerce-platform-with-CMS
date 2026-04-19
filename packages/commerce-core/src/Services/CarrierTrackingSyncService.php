<?php

namespace Platform\CommerceCore\Services;

use Illuminate\Support\Carbon;
use Platform\CommerceCore\Models\ShipmentRecord;
use Platform\CommerceCore\Repositories\ShipmentRecordRepository;
use Platform\CommerceCore\ShipmentTracking\CarrierTrackingSyncResult;
use Platform\CommerceCore\Support\CarrierTrackingProviderRegistry;

class CarrierTrackingSyncService
{
    public function __construct(
        protected CarrierTrackingProviderRegistry $providerRegistry,
        protected ShipmentRecordRepository $shipmentRecordRepository,
    ) {}

    public function syncShipmentRecord(ShipmentRecord $shipmentRecord, ?Carbon $syncedAt = null): CarrierTrackingSyncResult
    {
        $shipmentRecord->loadMissing('carrier');

        if (! $shipmentRecord->carrier) {
            return $this->persistSyncResult($shipmentRecord, CarrierTrackingSyncResult::failed('Shipment carrier is missing.'), $syncedAt);
        }

        if (! $shipmentRecord->tracking_number) {
            return $this->persistSyncResult($shipmentRecord, CarrierTrackingSyncResult::failed('Tracking number is missing.'), $syncedAt);
        }

        if (! $shipmentRecord->carrier->trackingSyncConfigured()) {
            return $this->persistSyncResult($shipmentRecord, CarrierTrackingSyncResult::skipped('Carrier tracking sync is disabled.'), $syncedAt);
        }

        $result = $this->providerRegistry
            ->forCarrier($shipmentRecord->carrier)
            ->fetchTracking($shipmentRecord->carrier, $shipmentRecord);

        return $this->persistSyncResult($shipmentRecord, $result, $syncedAt);
    }

    protected function persistSyncResult(
        ShipmentRecord $shipmentRecord,
        CarrierTrackingSyncResult $result,
        ?Carbon $syncedAt = null,
    ): CarrierTrackingSyncResult {
        $this->shipmentRecordRepository->update([
            'last_tracking_synced_at' => ($syncedAt ?? now())->toDateTimeString(),
            'last_tracking_sync_status' => $result->status,
            'last_tracking_sync_message' => $result->message,
        ], $shipmentRecord->id);

        return $result;
    }
}
