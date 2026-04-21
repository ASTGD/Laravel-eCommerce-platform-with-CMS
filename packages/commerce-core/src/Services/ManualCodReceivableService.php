<?php

namespace Platform\CommerceCore\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Platform\CommerceCore\Models\CodSettlement;

class ManualCodReceivableService
{
    public function __construct(
        protected CodSettlementService $codSettlementService,
    ) {}

    public function courierSummaries(): Collection
    {
        return CodSettlement::query()
            ->with('carrier:id,name')
            ->whereNotNull('shipment_carrier_id')
            ->whereIn('status', $this->summaryStatuses())
            ->get()
            ->groupBy('shipment_carrier_id')
            ->map(function (Collection $settlements, $carrierId): array {
                $firstSettlement = $settlements->first();

                $receivableTotal = round((float) $settlements->sum('net_amount'), 2);
                $receivedTotal = round((float) $settlements->sum('remitted_amount'), 2);
                $pendingTotal = round((float) $settlements->sum(fn (CodSettlement $settlement) => $settlement->outstanding_amount), 2);

                return [
                    'carrier_id'                    => (int) $carrierId,
                    'courier_name'                  => $firstSettlement?->carrier?->name ?: 'Unknown Courier',
                    'settlement_count'              => $settlements->count(),
                    'receivable_total'              => $receivableTotal,
                    'receivable_total_formatted'    => core()->formatBasePrice($receivableTotal),
                    'received_total'                => $receivedTotal,
                    'received_total_formatted'      => core()->formatBasePrice($receivedTotal),
                    'pending_total'                 => $pendingTotal,
                    'pending_total_formatted'       => core()->formatBasePrice($pendingTotal),
                    'can_record_receipt'            => $pendingTotal > 0,
                ];
            })
            ->sortBy('courier_name')
            ->values();
    }

    public function recordReceipt(
        int $carrierId,
        float $amount,
        ?string $note = null,
        ?int $actorAdminId = null,
    ): array {
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Enter the amount your business received from the courier.',
            ]);
        }

        return DB::transaction(function () use ($carrierId, $amount, $note, $actorAdminId) {
            $pendingSettlements = CodSettlement::query()
                ->where('shipment_carrier_id', $carrierId)
                ->whereIn('status', $this->allocatableStatuses())
                ->orderByRaw('COALESCE(collected_at, created_at) asc')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $pendingTotal = round((float) $pendingSettlements->sum(fn (CodSettlement $settlement) => $settlement->outstanding_amount), 2);

            if ($pendingTotal <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'This courier has no courier-collected COD waiting to be received by the merchant right now.',
                ]);
            }

            if ($amount > $pendingTotal) {
                throw ValidationException::withMessages([
                    'amount' => 'This courier only has '.core()->formatBasePrice($pendingTotal).' still pending for merchant receipt.',
                ]);
            }

            $remainingAmount = round($amount, 2);
            $allocationCount = 0;

            foreach ($pendingSettlements as $settlement) {
                if ($remainingAmount <= 0) {
                    break;
                }

                $outstandingAmount = round($settlement->outstanding_amount, 2);

                if ($outstandingAmount <= 0) {
                    continue;
                }

                $allocatedAmount = min($remainingAmount, $outstandingAmount);
                $newRemittedAmount = round((float) $settlement->remitted_amount + $allocatedAmount, 2);
                $isSettled = $newRemittedAmount >= round((float) $settlement->net_amount, 2);

                $this->codSettlementService->updateSettlement(
                    $settlement,
                    [
                        'status'          => $isSettled ? CodSettlement::STATUS_SETTLED : CodSettlement::STATUS_REMITTED,
                        'remitted_amount' => $newRemittedAmount,
                        'notes'           => $this->appendReceiptNote($settlement->notes, $allocatedAmount, $note),
                    ],
                    $actorAdminId,
                );

                $remainingAmount = round($remainingAmount - $allocatedAmount, 2);
                $allocationCount++;
            }

            return [
                'allocated_amount' => round($amount - $remainingAmount, 2),
                'allocation_count' => $allocationCount,
            ];
        });
    }

    protected function summaryStatuses(): array
    {
        return [
            CodSettlement::STATUS_COLLECTED_BY_CARRIER,
            CodSettlement::STATUS_REMITTED,
            CodSettlement::STATUS_SETTLED,
        ];
    }

    protected function allocatableStatuses(): array
    {
        return [
            CodSettlement::STATUS_COLLECTED_BY_CARRIER,
            CodSettlement::STATUS_REMITTED,
        ];
    }

    protected function appendReceiptNote(?string $existingNote, float $allocatedAmount, ?string $note): string
    {
        $segments = array_filter([
            trim((string) $existingNote),
            'Basic COD receipt recorded: '.number_format($allocatedAmount, 2).'.',
            $note ? 'Merchant note: '.trim($note) : null,
        ]);

        return implode("\n\n", $segments);
    }
}
