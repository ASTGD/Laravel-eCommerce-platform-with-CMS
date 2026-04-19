<?php

namespace Platform\CommerceCore\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Platform\CommerceCore\Models\CodSettlement;
use Platform\CommerceCore\Models\SettlementBatch;
use Platform\CommerceCore\Models\SettlementBatchItem;
use Webkul\Sales\Models\Order;

class SettlementBatchService
{
    public function __construct(protected CodSettlementService $codSettlementService) {}

    public function detailSummary(SettlementBatch $batch): array
    {
        $batch->loadMissing('items.codSettlement');

        $settledCount = 0;
        $shortCount = 0;
        $disputedCount = 0;
        $writtenOffCount = 0;

        foreach ($batch->items as $item) {
            $status = $item->codSettlement?->status;

            if ($status === CodSettlement::STATUS_SETTLED) {
                $settledCount++;
            }

            if ($status === CodSettlement::STATUS_SHORT_SETTLED || (float) $item->short_amount > 0) {
                $shortCount++;
            }

            if ($status === CodSettlement::STATUS_DISPUTED) {
                $disputedCount++;
            }

            if ($status === CodSettlement::STATUS_WRITTEN_OFF) {
                $writtenOffCount++;
            }
        }

        return [
            'settlements_count' => $batch->items->count(),
            'settled_count' => $settledCount,
            'short_count' => $shortCount,
            'disputed_count' => $disputedCount,
            'written_off_count' => $writtenOffCount,
            'requires_attention' => $shortCount > 0 || $disputedCount > 0 || $writtenOffCount > 0,
            'reconciliation_gap_amount' => $batch->reconciliation_gap_amount,
        ];
    }

    public function forOrder(Order $order): Collection
    {
        return SettlementBatch::query()
            ->with(['carrier', 'items.codSettlement'])
            ->whereHas('items.codSettlement', fn ($query) => $query->where('order_id', $order->id))
            ->latest()
            ->get();
    }

    public function eligibleSettlements(?int $carrierId = null): Collection
    {
        return CodSettlement::query()
            ->with(['order', 'shipmentRecord', 'carrier'])
            ->whereDoesntHave('batchItem')
            ->when($carrierId, fn ($query) => $query->where('shipment_carrier_id', $carrierId))
            ->whereNotIn('status', [
                CodSettlement::STATUS_SETTLED,
                CodSettlement::STATUS_WRITTEN_OFF,
            ])
            ->orderBy('id')
            ->get();
    }

    public function createBatch(array $data, ?int $actorAdminId = null): SettlementBatch
    {
        return DB::transaction(function () use ($data, $actorAdminId) {
            $settlements = CodSettlement::query()
                ->with(['carrier', 'order', 'shipmentRecord'])
                ->whereIn('id', $data['settlement_ids'])
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($settlements->count() !== count(array_unique($data['settlement_ids']))) {
                throw ValidationException::withMessages([
                    'settlement_ids' => 'One or more selected COD settlements could not be loaded.',
                ]);
            }

            foreach ($settlements as $settlement) {
                if ($settlement->batchItem()->exists()) {
                    throw ValidationException::withMessages([
                        'settlement_ids' => 'One or more selected COD settlements are already attached to another batch.',
                    ]);
                }
            }

            $carrierIds = $settlements->pluck('shipment_carrier_id')->filter()->unique()->values();

            if ($carrierIds->count() > 1) {
                throw ValidationException::withMessages([
                    'settlement_ids' => 'A settlement batch can only include COD settlements from one carrier.',
                ]);
            }

            if (
                ! empty($data['shipment_carrier_id'])
                && $carrierIds->isNotEmpty()
                && (int) $carrierIds->first() !== (int) $data['shipment_carrier_id']
            ) {
                throw ValidationException::withMessages([
                    'shipment_carrier_id' => 'The selected carrier does not match the selected COD settlements.',
                ]);
            }

            $this->validateBatchStatusRequirements(
                status: $data['status'],
                notes: $data['notes'] ?? null,
                remittedAmounts: $data['remitted_amounts'] ?? [],
            );

            $batch = SettlementBatch::query()->create([
                'shipment_carrier_id' => $data['shipment_carrier_id'] ?: $carrierIds->first(),
                'created_by_admin_id' => $actorAdminId,
                'updated_by_admin_id' => $actorAdminId,
                'reference' => $data['reference'],
                'payout_method' => $data['payout_method'] ?: $settlements->first()?->carrier?->default_payout_method,
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['settlement_ids'] as $settlementId) {
                $settlement = $settlements->get((int) $settlementId);
                $expectedAmount = (float) $settlement->net_amount;
                $remittedAmount = (float) ($data['remitted_amounts'][$settlementId] ?? $expectedAmount);
                $adjustmentAmount = (float) ($data['adjustment_amounts'][$settlementId] ?? 0);
                $shortAmount = max(0, $expectedAmount - $remittedAmount - $adjustmentAmount);

                $item = $batch->items()->create([
                    'cod_settlement_id' => $settlement->id,
                    'expected_amount' => $expectedAmount,
                    'remitted_amount' => $remittedAmount,
                    'adjustment_amount' => $adjustmentAmount,
                    'short_amount' => $shortAmount,
                    'note' => $data['item_notes'][$settlementId] ?? null,
                ]);

                $this->syncSettlementFromBatchItem($settlement, $batch, $item, $actorAdminId);
            }

            $this->recalculateBatchTotals($batch);

            return $batch->fresh(['carrier', 'items.codSettlement.order', 'items.codSettlement.shipmentRecord']);
        });
    }

    public function updateBatch(SettlementBatch $batch, array $data, ?int $actorAdminId = null): SettlementBatch
    {
        return DB::transaction(function () use ($batch, $data, $actorAdminId) {
            $this->validateBatchStatusRequirements(
                status: $data['status'],
                notes: $data['notes'] ?? null,
                remittedAmounts: $batch->items->pluck('remitted_amount')->all(),
            );

            $batch->fill([
                'updated_by_admin_id' => $actorAdminId,
                'reference' => $data['reference'],
                'payout_method' => $data['payout_method'] ?? $batch->payout_method,
                'status' => $data['status'],
                'notes' => $data['notes'] ?? $batch->notes,
            ]);

            if (
                in_array($batch->status, [
                    SettlementBatch::STATUS_REMITTED,
                    SettlementBatch::STATUS_RECEIVED,
                    SettlementBatch::STATUS_RECONCILED,
                    SettlementBatch::STATUS_DISPUTED,
                ], true)
                && ! $batch->remitted_at
            ) {
                $batch->remitted_at = now();
            }

            if (
                in_array($batch->status, [
                    SettlementBatch::STATUS_RECEIVED,
                    SettlementBatch::STATUS_RECONCILED,
                ], true)
                && ! $batch->received_at
            ) {
                $batch->received_at = now();
            }

            $batch->save();

            $batch->loadMissing('items.codSettlement');

            foreach ($batch->items as $item) {
                $this->syncSettlementFromBatchItem($item->codSettlement, $batch, $item, $actorAdminId);
            }

            $this->recalculateBatchTotals($batch);

            return $batch->fresh(['carrier', 'items.codSettlement.order', 'items.codSettlement.shipmentRecord']);
        });
    }

    protected function syncSettlementFromBatchItem(
        CodSettlement $codSettlement,
        SettlementBatch $batch,
        SettlementBatchItem $item,
        ?int $actorAdminId = null,
    ): void {
        $status = match ($batch->status) {
            SettlementBatch::STATUS_RECONCILED => (float) $item->short_amount > 0
                ? CodSettlement::STATUS_SHORT_SETTLED
                : CodSettlement::STATUS_SETTLED,
            SettlementBatch::STATUS_DISPUTED => CodSettlement::STATUS_DISPUTED,
            SettlementBatch::STATUS_REMITTED,
            SettlementBatch::STATUS_RECEIVED => CodSettlement::STATUS_REMITTED,
            default => $codSettlement->status,
        };

        $this->codSettlementService->updateSettlement($codSettlement, [
            'status' => $status,
            'collected_amount' => (float) $codSettlement->collected_amount > 0
                ? (float) $codSettlement->collected_amount
                : (float) $codSettlement->expected_amount,
            'remitted_amount' => (float) $item->remitted_amount,
            'short_amount' => (float) $item->short_amount,
            'disputed_amount' => $status === CodSettlement::STATUS_DISPUTED
                ? max((float) $codSettlement->disputed_amount, (float) $item->short_amount)
                : (float) $codSettlement->disputed_amount,
            'carrier_fee_amount' => (float) $codSettlement->carrier_fee_amount,
            'cod_fee_amount' => (float) $codSettlement->cod_fee_amount,
            'return_fee_amount' => (float) $codSettlement->return_fee_amount,
            'dispute_note' => $status === CodSettlement::STATUS_DISPUTED
                ? ($item->note ?: $batch->notes ?: $codSettlement->dispute_note)
                : $codSettlement->dispute_note,
            'notes' => $batch->notes ?: $codSettlement->notes,
        ], $actorAdminId);
    }

    protected function recalculateBatchTotals(SettlementBatch $batch): void
    {
        $batch->loadMissing('items.codSettlement');

        $grossExpectedAmount = (float) $batch->items->sum(fn (SettlementBatchItem $item) => (float) $item->codSettlement->expected_amount);
        $grossRemittedAmount = (float) $batch->items->sum(fn (SettlementBatchItem $item) => (float) $item->remitted_amount);
        $totalAdjustmentAmount = (float) $batch->items->sum(fn (SettlementBatchItem $item) => (float) $item->adjustment_amount);
        $totalShortAmount = (float) $batch->items->sum(fn (SettlementBatchItem $item) => (float) $item->short_amount);
        $totalDeductionsAmount = (float) $batch->items->sum(
            fn (SettlementBatchItem $item) => (float) $item->codSettlement->carrier_fee_amount
                + (float) $item->codSettlement->cod_fee_amount
                + (float) $item->codSettlement->return_fee_amount
        );

        $batch->forceFill([
            'gross_expected_amount' => $grossExpectedAmount,
            'gross_remitted_amount' => $grossRemittedAmount,
            'total_adjustment_amount' => $totalAdjustmentAmount,
            'total_short_amount' => $totalShortAmount,
            'total_deductions_amount' => $totalDeductionsAmount,
            'net_amount' => max(0, $grossRemittedAmount + $totalAdjustmentAmount),
        ])->save();
    }

    protected function validateBatchStatusRequirements(string $status, ?string $notes, array $remittedAmounts): void
    {
        $notes = trim((string) $notes);
        $hasRemittedAmount = collect($remittedAmounts)
            ->map(fn ($amount) => (float) $amount)
            ->contains(fn (float $amount) => $amount > 0);

        if (
            in_array($status, [
                SettlementBatch::STATUS_REMITTED,
                SettlementBatch::STATUS_RECEIVED,
                SettlementBatch::STATUS_RECONCILED,
                SettlementBatch::STATUS_DISPUTED,
            ], true)
            && ! $hasRemittedAmount
        ) {
            throw ValidationException::withMessages([
                'status' => 'This batch status requires at least one remitted amount greater than zero.',
            ]);
        }

        if ($status === SettlementBatch::STATUS_DISPUTED && $notes === '') {
            throw ValidationException::withMessages([
                'notes' => 'Disputed batches require an operator note.',
            ]);
        }
    }
}
