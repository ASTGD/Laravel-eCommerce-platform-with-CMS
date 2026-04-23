<?php

namespace Platform\CommerceCore\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Platform\CommerceCore\Models\ShipmentHandoverBatch;
use Platform\CommerceCore\Models\ShipmentRecord;

class ManualShipmentHandoverService
{
    public function __construct(
        protected ShipmentRecordService $shipmentRecordService,
    ) {}

    public function createDraftBatch(array $shipmentRecordIds, array $attributes, ?int $actorAdminId = null): ShipmentHandoverBatch
    {
        return DB::transaction(function () use ($shipmentRecordIds, $attributes, $actorAdminId) {
            $shipments = $this->selectedReadyShipments($shipmentRecordIds);
            $batch = $this->upsertBatch($shipments, $attributes, $actorAdminId);

            $this->assignShipmentsToBatch($shipments, $batch, $actorAdminId);

            return $batch->fresh(['carrier', 'shipments.order']);
        });
    }

    public function confirmHandover(array $shipmentRecordIds, array $attributes, ?int $actorAdminId = null): ShipmentHandoverBatch
    {
        return DB::transaction(function () use ($shipmentRecordIds, $attributes, $actorAdminId) {
            $shipments = $this->selectedReadyShipments($shipmentRecordIds);
            $batch = $this->upsertBatch($shipments, $attributes, $actorAdminId);

            $this->assignShipmentsToBatch($shipments, $batch, $actorAdminId);

            foreach ($shipments as $shipmentRecord) {
                $this->shipmentRecordService->confirmHandover(
                    $shipmentRecord,
                    $batch,
                    $actorAdminId,
                    $attributes['notes'] ?? null,
                );
            }

            $batch->confirmed_at = $batch->handover_at;
            $batch->updated_by_admin_id = $actorAdminId;
            $batch->save();

            return $batch->fresh(['carrier', 'shipments.order']);
        });
    }

    public function manifestPreview(array $shipmentRecordIds, array $attributes): array
    {
        $shipments = $this->selectedReadyShipments($shipmentRecordIds);
        $carrier = $this->ensureSingleCarrier($shipments);
        $handoverType = $attributes['handover_type'];
        $handoverAt = $attributes['handover_at'];
        $parcelCount = (int) $shipments->sum(fn (ShipmentRecord $shipmentRecord) => max(1, (int) $shipmentRecord->package_count));
        $totalCodAmount = round((float) $shipments->sum('cod_amount_expected'), 2);

        return [
            'reference' => $this->nextReference(),
            'carrier' => $carrier,
            'handover_type' => $handoverType,
            'handover_type_label' => ShipmentHandoverBatch::typeLabels()[$handoverType] ?? 'Courier Pickup',
            'handover_at' => $handoverAt,
            'parcel_count' => $parcelCount,
            'total_cod_amount' => $totalCodAmount,
            'total_cod_amount_formatted' => core()->formatBasePrice($totalCodAmount),
            'receiver_name' => trim((string) ($attributes['receiver_name'] ?? '')) ?: null,
            'notes' => trim((string) ($attributes['notes'] ?? '')) ?: null,
            'shipments' => $shipments,
        ];
    }

    protected function selectedReadyShipments(array $shipmentRecordIds): Collection
    {
        $shipmentRecordIds = collect($shipmentRecordIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($shipmentRecordIds->isEmpty()) {
            throw ValidationException::withMessages([
                'selected_shipments' => 'Select at least one parcel from Parcel Ready for Handover.',
            ]);
        }

        $shipments = ShipmentRecord::query()
            ->with(['order', 'carrier', 'handoverBatch'])
            ->whereIn('id', $shipmentRecordIds)
            ->where('status', ShipmentRecord::STATUS_READY_FOR_PICKUP)
            ->lockForUpdate()
            ->get();

        if ($shipments->count() !== $shipmentRecordIds->count()) {
            throw ValidationException::withMessages([
                'selected_shipments' => 'One or more selected parcels are no longer ready for handover.',
            ]);
        }

        return $shipments;
    }

    protected function upsertBatch(Collection $shipments, array $attributes, ?int $actorAdminId = null): ShipmentHandoverBatch
    {
        $carrier = $this->ensureSingleCarrier($shipments);
        $existingBatchIds = $shipments
            ->pluck('handover_batch_id')
            ->filter()
            ->unique()
            ->values();

        if ($existingBatchIds->count() > 1) {
            throw ValidationException::withMessages([
                'selected_shipments' => 'Selected parcels belong to different draft handover batches. Clear the selection and choose one batch at a time.',
            ]);
        }

        $batch = null;

        if ($existingBatchIds->count() === 1) {
            $batch = ShipmentHandoverBatch::query()->lockForUpdate()->find($existingBatchIds->first());

            if ($batch?->confirmed_at) {
                throw ValidationException::withMessages([
                    'selected_shipments' => 'A confirmed handover batch cannot be edited from this page.',
                ]);
            }
        }

        $parcelCount = (int) $shipments->sum(fn (ShipmentRecord $shipmentRecord) => max(1, (int) $shipmentRecord->package_count));
        $totalCodAmount = round((float) $shipments->sum('cod_amount_expected'), 2);

        if (! $batch) {
            $batch = new ShipmentHandoverBatch([
                'reference' => $this->nextReference(),
                'created_by_admin_id' => $actorAdminId,
            ]);
        }

        $batch->fill([
            'shipment_carrier_id' => $carrier->id,
            'updated_by_admin_id' => $actorAdminId,
            'handover_type' => $attributes['handover_type'],
            'handover_at' => $attributes['handover_at'],
            'parcel_count' => $parcelCount,
            'total_cod_amount' => $totalCodAmount,
            'receiver_name' => trim((string) ($attributes['receiver_name'] ?? '')) ?: null,
            'notes' => trim((string) ($attributes['notes'] ?? '')) ?: null,
        ]);

        $batch->save();

        return $batch;
    }

    protected function assignShipmentsToBatch(Collection $shipments, ShipmentHandoverBatch $batch, ?int $actorAdminId = null): void
    {
        ShipmentRecord::query()
            ->whereIn('id', $shipments->pluck('id'))
            ->update([
                'handover_batch_id' => $batch->id,
                'handover_mode' => $batch->handover_type,
                'updated_by_admin_id' => $actorAdminId,
                'updated_at' => now(),
            ]);
    }

    protected function ensureSingleCarrier(Collection $shipments)
    {
        $carrierIds = $shipments->pluck('shipment_carrier_id')->filter()->unique()->values();

        if ($carrierIds->count() !== 1) {
            throw ValidationException::withMessages([
                'selected_shipments' => 'Select parcels for one courier at a time when creating or confirming a handover batch.',
            ]);
        }

        return $shipments->first()->carrier;
    }

    protected function nextReference(): string
    {
        $prefix = 'HB-'.now()->format('Ymd');
        $count = ShipmentHandoverBatch::query()
            ->where('reference', 'like', $prefix.'-%')
            ->count() + 1;

        return sprintf('%s-%04d', $prefix, $count);
    }
}
