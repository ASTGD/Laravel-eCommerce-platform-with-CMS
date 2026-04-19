<?php

namespace Platform\CommerceCore\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Platform\CommerceCore\Models\CodSettlement;
use Platform\CommerceCore\Models\ShipmentRecord;
use Webkul\Sales\Models\Order;

class CodSettlementService
{
    public function detailSummary(CodSettlement $codSettlement): array
    {
        return [
            'health_label' => $codSettlement->health_label,
            'outstanding_amount' => $codSettlement->outstanding_amount,
            'requires_attention' => $codSettlement->requires_attention,
            'batch_reference' => $codSettlement->batchItem?->batch?->reference,
            'batch_id' => $codSettlement->batchItem?->settlement_batch_id,
        ];
    }

    public function forOrder(Order $order): Collection
    {
        return CodSettlement::query()
            ->with(['shipmentRecord', 'carrier', 'batchItem.batch'])
            ->where('order_id', $order->id)
            ->latest()
            ->get();
    }

    public function syncFromShipmentRecord(ShipmentRecord $shipmentRecord, ?int $actorAdminId = null): ?CodSettlement
    {
        if ((float) $shipmentRecord->cod_amount_expected <= 0) {
            return null;
        }

        return DB::transaction(function () use ($shipmentRecord, $actorAdminId) {
            $settlement = CodSettlement::query()->firstOrNew([
                'shipment_record_id' => $shipmentRecord->id,
            ]);

            $expectedAmount = (float) $shipmentRecord->cod_amount_expected;
            $carrierFeeAmount = (float) $shipmentRecord->carrier_fee_amount;
            $codFeeAmount = (float) $shipmentRecord->cod_fee_amount;
            $returnFeeAmount = (float) $shipmentRecord->return_fee_amount;
            $netAmount = max(0, $expectedAmount - $carrierFeeAmount - $codFeeAmount - $returnFeeAmount);

            $collectedAmount = max(
                (float) ($settlement->collected_amount ?? 0),
                (float) $shipmentRecord->cod_amount_collected
            );

            $remittedAmount = (float) ($settlement->remitted_amount ?? 0);
            $disputedAmount = (float) ($settlement->disputed_amount ?? 0);
            $shortAmount = $this->calculateShortAmount($netAmount, $remittedAmount, $disputedAmount);

            $settlement->fill([
                'order_id' => $shipmentRecord->order_id,
                'shipment_carrier_id' => $shipmentRecord->shipment_carrier_id,
                'updated_by_admin_id' => $actorAdminId ?: $shipmentRecord->updated_by_admin_id,
                'status' => $settlement->status ?: CodSettlement::STATUS_EXPECTED,
                'expected_amount' => $expectedAmount,
                'collected_amount' => $collectedAmount,
                'carrier_fee_amount' => $carrierFeeAmount,
                'cod_fee_amount' => $codFeeAmount,
                'return_fee_amount' => $returnFeeAmount,
                'net_amount' => $netAmount,
                'short_amount' => $shortAmount,
            ]);

            if (! $settlement->exists) {
                $settlement->created_by_admin_id = $actorAdminId ?: $shipmentRecord->created_by_admin_id;
            }

            $settlement->save();

            return $settlement->fresh(['shipmentRecord', 'order', 'carrier']);
        });
    }

    public function updateSettlement(CodSettlement $codSettlement, array $data, ?int $actorAdminId = null): CodSettlement
    {
        return DB::transaction(function () use ($codSettlement, $data, $actorAdminId) {
            $status = $data['status'];
            $expectedAmount = (float) $codSettlement->expected_amount;
            $carrierFeeAmount = array_key_exists('carrier_fee_amount', $data)
                ? (float) $data['carrier_fee_amount']
                : (float) $codSettlement->carrier_fee_amount;
            $codFeeAmount = array_key_exists('cod_fee_amount', $data)
                ? (float) $data['cod_fee_amount']
                : (float) $codSettlement->cod_fee_amount;
            $returnFeeAmount = array_key_exists('return_fee_amount', $data)
                ? (float) $data['return_fee_amount']
                : (float) $codSettlement->return_fee_amount;
            $netAmount = max(0, $expectedAmount - $carrierFeeAmount - $codFeeAmount - $returnFeeAmount);

            $collectedAmount = array_key_exists('collected_amount', $data)
                ? (float) $data['collected_amount']
                : (float) $codSettlement->collected_amount;
            $remittedAmount = array_key_exists('remitted_amount', $data)
                ? (float) $data['remitted_amount']
                : (float) $codSettlement->remitted_amount;
            $disputedAmount = array_key_exists('disputed_amount', $data)
                ? (float) $data['disputed_amount']
                : (float) $codSettlement->disputed_amount;
            $shortAmount = array_key_exists('short_amount', $data) && $data['short_amount'] !== null
                ? (float) $data['short_amount']
                : $this->calculateShortAmount($netAmount, $remittedAmount, $disputedAmount);

            [$collectedAmount, $remittedAmount, $shortAmount, $disputedAmount] = $this->normalizeFinancialState(
                status: $status,
                netAmount: $netAmount,
                collectedAmount: $collectedAmount,
                remittedAmount: $remittedAmount,
                shortAmount: $shortAmount,
                disputedAmount: $disputedAmount,
                disputeNote: $data['dispute_note'] ?? $codSettlement->dispute_note,
                notes: $data['notes'] ?? $codSettlement->notes,
            );

            $codSettlement->fill([
                'status' => $status,
                'updated_by_admin_id' => $actorAdminId,
                'collected_amount' => $collectedAmount,
                'remitted_amount' => $remittedAmount,
                'short_amount' => $shortAmount,
                'disputed_amount' => $disputedAmount,
                'carrier_fee_amount' => $carrierFeeAmount,
                'cod_fee_amount' => $codFeeAmount,
                'return_fee_amount' => $returnFeeAmount,
                'net_amount' => $netAmount,
                'dispute_note' => $data['dispute_note'] ?? $codSettlement->dispute_note,
                'notes' => $data['notes'] ?? $codSettlement->notes,
            ]);

            $eventAt = now();

            if (
                in_array($status, [
                    CodSettlement::STATUS_COLLECTED_BY_CARRIER,
                    CodSettlement::STATUS_REMITTED,
                    CodSettlement::STATUS_SETTLED,
                    CodSettlement::STATUS_SHORT_SETTLED,
                ], true)
                && ! $codSettlement->collected_at
            ) {
                $codSettlement->collected_at = $eventAt;
            }

            if (
                in_array($status, [
                    CodSettlement::STATUS_REMITTED,
                    CodSettlement::STATUS_SETTLED,
                    CodSettlement::STATUS_SHORT_SETTLED,
                ], true)
                && ! $codSettlement->remitted_at
            ) {
                $codSettlement->remitted_at = $eventAt;
            }

            if (
                in_array($status, [
                    CodSettlement::STATUS_SETTLED,
                    CodSettlement::STATUS_SHORT_SETTLED,
                ], true)
                && ! $codSettlement->settled_at
            ) {
                $codSettlement->settled_at = $eventAt;
            }

            $codSettlement->save();

            return $codSettlement->fresh(['shipmentRecord', 'order', 'carrier']);
        });
    }

    protected function calculateShortAmount(float $netAmount, float $remittedAmount, float $disputedAmount): float
    {
        return max(0, $netAmount - $remittedAmount - $disputedAmount);
    }

    protected function normalizeFinancialState(
        string $status,
        float $netAmount,
        float $collectedAmount,
        float $remittedAmount,
        float $shortAmount,
        float $disputedAmount,
        ?string $disputeNote,
        ?string $notes,
    ): array {
        $disputeNote = trim((string) $disputeNote);
        $notes = trim((string) $notes);

        if (
            in_array($status, [
                CodSettlement::STATUS_COLLECTED_BY_CARRIER,
                CodSettlement::STATUS_REMITTED,
                CodSettlement::STATUS_SETTLED,
                CodSettlement::STATUS_SHORT_SETTLED,
            ], true)
            && $collectedAmount <= 0
        ) {
            $collectedAmount = $netAmount;
        }

        if ($status === CodSettlement::STATUS_REMITTED && $remittedAmount <= 0) {
            throw ValidationException::withMessages([
                'remitted_amount' => 'Remitted status requires a remitted amount greater than zero.',
            ]);
        }

        if ($status === CodSettlement::STATUS_SETTLED) {
            if ($remittedAmount < $netAmount) {
                throw ValidationException::withMessages([
                    'remitted_amount' => 'Settled status requires the full net amount to be remitted.',
                ]);
            }

            if ($disputedAmount > 0 || $shortAmount > 0) {
                throw ValidationException::withMessages([
                    'status' => 'Settled status cannot keep short or disputed amounts.',
                ]);
            }

            $shortAmount = 0;
            $disputedAmount = 0;
        }

        if ($status === CodSettlement::STATUS_SHORT_SETTLED) {
            $shortAmount = max($shortAmount, $this->calculateShortAmount($netAmount, $remittedAmount, $disputedAmount));

            if ($shortAmount <= 0) {
                throw ValidationException::withMessages([
                    'short_amount' => 'Short Settled status requires a remaining short amount.',
                ]);
            }
        }

        if ($status === CodSettlement::STATUS_DISPUTED) {
            if ($disputeNote === '') {
                throw ValidationException::withMessages([
                    'dispute_note' => 'Disputed status requires a dispute note.',
                ]);
            }

            $disputedAmount = max(
                $disputedAmount,
                $this->calculateShortAmount($netAmount, $remittedAmount, 0),
            );

            if ($disputedAmount <= 0) {
                throw ValidationException::withMessages([
                    'disputed_amount' => 'Disputed status requires a disputed amount.',
                ]);
            }
        }

        if ($status === CodSettlement::STATUS_WRITTEN_OFF) {
            if ($notes === '') {
                throw ValidationException::withMessages([
                    'notes' => 'Written Off status requires an operator note.',
                ]);
            }

            if (
                $this->calculateShortAmount($netAmount, $remittedAmount, $disputedAmount) <= 0
                && $shortAmount <= 0
                && $disputedAmount <= 0
            ) {
                throw ValidationException::withMessages([
                    'status' => 'Written Off status requires an outstanding, short, or disputed balance.',
                ]);
            }
        }

        return [$collectedAmount, $remittedAmount, $shortAmount, $disputedAmount];
    }
}
