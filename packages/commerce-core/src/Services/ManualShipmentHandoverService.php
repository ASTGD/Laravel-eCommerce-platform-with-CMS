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

            $this->syncBatchShipments($shipments, $batch, $actorAdminId);
            $this->assignShipmentsToBatch($shipments, $batch, $actorAdminId);

            return $batch->fresh(['carrier', 'shipments.order']);
        });
    }

    public function confirmPreparedBatch(array $shipmentRecordIds, ?int $actorAdminId = null): ShipmentHandoverBatch
    {
        return DB::transaction(function () use ($shipmentRecordIds, $actorAdminId) {
            $shipments = $this->selectedReadyShipments($shipmentRecordIds);
            $batch = $this->preparedBatchForSelection($shipments);

            foreach ($shipments as $shipmentRecord) {
                $this->shipmentRecordService->confirmHandover(
                    $shipmentRecord,
                    $batch,
                    $actorAdminId,
                    $batch->notes,
                );
            }

            $batch->confirmed_at = now();
            $batch->updated_by_admin_id = $actorAdminId;
            $batch->save();

            return $batch->fresh(['carrier', 'shipments.order']);
        });
    }

    public function manifestPreview(array $shipmentRecordIds, array $attributes): array
    {
        $shipments = $this->selectedReadyShipments($shipmentRecordIds);
        $carrier = $this->ensureSingleCarrier($shipments);
        $reference = $this->nextReference();

        return $this->previewPayload(
            $shipments,
            $carrier,
            $reference,
            $attributes['handover_type'],
            $attributes['handover_at'],
            trim((string) ($attributes['receiver_name'] ?? '')) ?: null,
            trim((string) ($attributes['notes'] ?? '')) ?: null,
        );
    }

    public function handoverSheetPreview(ShipmentHandoverBatch $batch): array
    {
        $batch->loadMissing(['carrier', 'shipments.order']);

        $shipments = $batch->shipments
            ->sortBy('id')
            ->values();

        return $this->previewPayload(
            $shipments,
            $batch->carrier,
            $batch->reference,
            $batch->handover_type,
            $batch->handover_at,
            $batch->receiver_name,
            $batch->notes,
        );
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

        $existingBatches = ShipmentHandoverBatch::query()
            ->whereIn('id', $existingBatchIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $draftBatchIds = $existingBatchIds
            ->filter(fn ($batchId) => ! $existingBatches->get($batchId)?->confirmed_at)
            ->values();

        $confirmedBatchIds = $existingBatchIds
            ->filter(fn ($batchId) => (bool) $existingBatches->get($batchId)?->confirmed_at)
            ->values();

        if ($draftBatchIds->count() > 1) {
            throw ValidationException::withMessages([
                'selected_shipments' => 'Selected parcels belong to different draft handover batches. Clear the selection and choose one batch at a time.',
            ]);
        }

        if ($confirmedBatchIds->isNotEmpty()) {
            ShipmentRecord::query()
                ->whereIn('id', $shipments->pluck('id'))
                ->whereIn('handover_batch_id', $confirmedBatchIds)
                ->where('status', ShipmentRecord::STATUS_READY_FOR_PICKUP)
                ->update([
                    'handover_batch_id' => null,
                    'updated_by_admin_id' => $actorAdminId,
                    'updated_at' => now(),
                ]);

            $shipments->each(function (ShipmentRecord $shipmentRecord) use ($confirmedBatchIds) {
                if (! $confirmedBatchIds->contains($shipmentRecord->handover_batch_id)) {
                    return;
                }

                $shipmentRecord->handover_batch_id = null;
                $shipmentRecord->setRelation('handoverBatch', null);
            });
        }

        $batch = null;

        if ($draftBatchIds->count() === 1) {
            $batch = $existingBatches->get($draftBatchIds->first());

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

    protected function syncBatchShipments(Collection $shipments, ShipmentHandoverBatch $batch, ?int $actorAdminId = null): void
    {
        $selectedShipmentIds = $shipments->pluck('id')->map(fn ($id) => (int) $id)->all();

        ShipmentRecord::query()
            ->where('handover_batch_id', $batch->id)
            ->where('status', ShipmentRecord::STATUS_READY_FOR_PICKUP)
            ->when($selectedShipmentIds !== [], fn ($query) => $query->whereNotIn('id', $selectedShipmentIds))
            ->update([
                'handover_batch_id' => null,
                'updated_by_admin_id' => $actorAdminId,
                'updated_at' => now(),
            ]);
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

    protected function preparedBatchForSelection(Collection $shipments): ShipmentHandoverBatch
    {
        $batchIds = $shipments
            ->pluck('handover_batch_id')
            ->filter()
            ->unique()
            ->values();

        if ($batchIds->count() !== 1) {
            throw ValidationException::withMessages([
                'selected_shipments' => 'Prepare the handover sheet before confirming handover.',
            ]);
        }

        $batch = ShipmentHandoverBatch::query()
            ->with(['carrier', 'shipments.order'])
            ->lockForUpdate()
            ->find($batchIds->first());

        if (! $batch || $batch->confirmed_at) {
            throw ValidationException::withMessages([
                'selected_shipments' => 'This handover sheet is no longer available for confirmation.',
            ]);
        }

        $selectedShipmentIds = $shipments
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();

        $batchShipmentIds = ShipmentRecord::query()
            ->where('handover_batch_id', $batch->id)
            ->where('status', ShipmentRecord::STATUS_READY_FOR_PICKUP)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();

        if ($selectedShipmentIds !== $batchShipmentIds) {
            throw ValidationException::withMessages([
                'selected_shipments' => 'Prepare the handover sheet for the current selection before confirming handover.',
            ]);
        }

        return $batch;
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

    protected function previewPayload(
        Collection $shipments,
        $carrier,
        string $reference,
        string $handoverType,
        $handoverAt,
        ?string $receiverName,
        ?string $notes,
    ): array {
        $parcelCount = (int) $shipments->sum(fn (ShipmentRecord $shipmentRecord) => max(1, (int) $shipmentRecord->package_count));
        $totalCodAmount = round((float) $shipments->sum('cod_amount_expected'), 2);

        return [
            'reference' => $reference,
            'carrier' => $carrier,
            'handover_type' => $handoverType,
            'handover_type_label' => ShipmentHandoverBatch::typeLabels()[$handoverType] ?? 'Courier Pickup',
            'handover_at' => $handoverAt,
            'parcel_count' => $parcelCount,
            'total_cod_amount' => $totalCodAmount,
            'total_cod_amount_formatted' => core()->formatBasePrice($totalCodAmount),
            'receiver_name' => $receiverName,
            'notes' => $notes,
            'shipments' => $shipments,
        ];
    }
}
