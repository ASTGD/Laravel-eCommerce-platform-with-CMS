<?php

namespace Platform\CommerceCore\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Platform\CommerceCore\Models\CodRemittance;
use Platform\CommerceCore\Models\CodRemittanceAllocation;
use Platform\CommerceCore\Models\CodSettlement;

class ManualCodReceivableService
{
    public function __construct(
        protected CodSettlementService $codSettlementService,
    ) {}

    public function courierSummaries(int $perPage = 10, ?string $search = null): LengthAwarePaginator
    {
        $summaries = CodSettlement::query()
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
                    'carrier_id' => (int) $carrierId,
                    'courier_name' => $firstSettlement?->carrier?->name ?: 'Unknown Courier',
                    'settlement_count' => $settlements->count(),
                    'receivable_total' => $receivableTotal,
                    'receivable_total_formatted' => core()->formatBasePrice($receivableTotal),
                    'received_total' => $receivedTotal,
                    'received_total_formatted' => core()->formatBasePrice($receivedTotal),
                    'pending_total' => $pendingTotal,
                    'pending_total_formatted' => core()->formatBasePrice($pendingTotal),
                    'can_record_receipt' => $pendingTotal > 0,
                ];
            })
            ->sortBy('courier_name')
            ->values();

        if ($search = $this->normalizeSearchTerm($search)) {
            $summaries = $summaries->filter(function (array $summary) use ($search) {
                return Str::contains(Str::lower($summary['courier_name']), $search);
            })->values();
        }

        return $this->paginateCollection($summaries, $perPage);
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
            $allocatedTotal = 0.0;

            $remittance = CodRemittance::query()->create([
                'shipment_carrier_id' => $carrierId,
                'created_by_admin_id' => $actorAdminId,
                'reference' => $this->makeReceiptReference(),
                'status' => CodRemittance::STATUS_ALLOCATED,
                'amount_received' => round($amount, 2),
                'allocated_amount' => 0,
                'unallocated_amount' => round($amount, 2),
                'received_at' => now(),
                'note' => $note,
            ]);

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
                        'status' => $isSettled ? CodSettlement::STATUS_SETTLED : CodSettlement::STATUS_REMITTED,
                        'remitted_amount' => $newRemittedAmount,
                        'notes' => $this->appendReceiptNote($settlement->notes, $allocatedAmount, $note),
                    ],
                    $actorAdminId,
                );

                $remainingAmount = round($remainingAmount - $allocatedAmount, 2);
                $allocatedTotal = round($allocatedTotal + $allocatedAmount, 2);
                $allocationCount++;

                CodRemittanceAllocation::query()->create([
                    'cod_remittance_id' => $remittance->id,
                    'cod_settlement_id' => $settlement->id,
                    'order_id' => $settlement->order_id,
                    'shipment_record_id' => $settlement->shipment_record_id,
                    'allocated_amount' => $allocatedAmount,
                    'status' => $isSettled
                        ? CodRemittanceAllocation::STATUS_SETTLED
                        : CodRemittanceAllocation::STATUS_ALLOCATED,
                ]);
            }

            $remittance->update([
                'status' => $remainingAmount > 0
                    ? CodRemittance::STATUS_PARTIALLY_ALLOCATED
                    : CodRemittance::STATUS_COMPLETED,
                'allocated_amount' => $allocatedTotal,
                'unallocated_amount' => round($remainingAmount, 2),
            ]);

            return [
                'allocated_amount' => round($amount - $remainingAmount, 2),
                'allocation_count' => $allocationCount,
                'remittance_id' => $remittance->id,
                'reference' => $remittance->reference,
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

    protected function normalizeSearchTerm(?string $search): ?string
    {
        $search = trim((string) $search);

        return $search !== '' ? Str::lower($search) : null;
    }

    protected function paginateCollection(Collection $collection, int $perPage): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage();
        $items = $collection
            ->slice(($page - 1) * $perPage, $perPage)
            ->values();

        return new LengthAwarePaginator(
            items: $items,
            total: $collection->count(),
            perPage: $perPage,
            currentPage: $page,
            options: [
                'path' => request()->url(),
                'query' => request()->query(),
            ],
        );
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

    protected function makeReceiptReference(): string
    {
        do {
            $reference = 'CODR-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (CodRemittance::query()->where('reference', $reference)->exists());

        return $reference;
    }
}
