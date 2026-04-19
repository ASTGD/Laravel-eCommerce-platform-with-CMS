<?php

namespace Platform\CommerceCore\Console\Commands;

use Illuminate\Console\Command;
use Platform\CommerceCore\Jobs\SyncShipmentTrackingJob;
use Platform\CommerceCore\Models\ShipmentRecord;
use Platform\CommerceCore\Services\CarrierTrackingSyncService;

class SyncShipmentTrackingCommand extends Command
{
    protected $signature = 'platform:shipments:sync-tracking
        {--carrier= : Restrict sync to shipment records for a carrier code}
        {--shipment-record= : Sync one shipment record id}
        {--sync-now : Run synchronously instead of dispatching jobs}';

    protected $description = 'Sync shipment tracking updates using the configured carrier integration foundation.';

    public function __construct(protected CarrierTrackingSyncService $carrierTrackingSyncService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $query = ShipmentRecord::query()
            ->with('carrier')
            ->when(
                $this->option('shipment-record'),
                fn ($builder, $shipmentRecordId) => $builder->whereKey((int) $shipmentRecordId),
            )
            ->when(
                $this->option('carrier'),
                fn ($builder, $carrierCode) => $builder->whereHas('carrier', fn ($carrierQuery) => $carrierQuery->where('code', $carrierCode)),
            );

        $shipmentRecords = $query->get();

        if ($shipmentRecords->isEmpty()) {
            $this->components->warn('No shipment records matched the sync filters.');

            return self::SUCCESS;
        }

        $processed = 0;

        foreach ($shipmentRecords as $shipmentRecord) {
            if ($this->option('sync-now')) {
                $result = $this->carrierTrackingSyncService->syncShipmentRecord($shipmentRecord);
                $this->line(sprintf(
                    '#%d [%s] %s',
                    $shipmentRecord->id,
                    strtoupper($result->status),
                    $result->message,
                ));
            } else {
                SyncShipmentTrackingJob::dispatch($shipmentRecord->id);
            }

            $processed++;
        }

        $this->components->info($this->option('sync-now')
            ? "Shipment tracking sync completed for {$processed} record(s)."
            : "Queued shipment tracking sync for {$processed} record(s).");

        return self::SUCCESS;
    }
}
