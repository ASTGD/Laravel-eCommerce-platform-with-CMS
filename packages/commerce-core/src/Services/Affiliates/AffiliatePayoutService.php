<?php

namespace Platform\CommerceCore\Services\Affiliates;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Platform\CommerceCore\Models\AffiliateCommission;
use Platform\CommerceCore\Models\AffiliatePayout;
use Platform\CommerceCore\Models\AffiliatePayoutCommissionAllocation;
use Platform\CommerceCore\Models\AffiliateProfile;

class AffiliatePayoutService
{
    public function __construct(protected AffiliateSettingsService $affiliateSettingsService) {}

    public function balanceFor(AffiliateProfile $profile): array
    {
        $approvedCommissions = (float) AffiliateCommission::query()
            ->where('affiliate_profile_id', $profile->id)
            ->where('status', AffiliateCommission::STATUS_APPROVED)
            ->sum('commission_amount');

        $pendingCommissions = (float) AffiliateCommission::query()
            ->where('affiliate_profile_id', $profile->id)
            ->where('status', AffiliateCommission::STATUS_PENDING)
            ->sum('commission_amount');

        $paidAllocations = $this->paidAllocationTotal($profile);
        $reservedAllocations = $this->reservedAllocationTotal($profile);
        $activeAllocationsAgainstApprovedCommissions = $this->activeAllocationTotalAgainstApprovedCommissions($profile);

        $paidPayouts = (float) AffiliatePayout::query()
            ->where('affiliate_profile_id', $profile->id)
            ->where('status', AffiliatePayout::STATUS_PAID)
            ->sum('amount');

        return [
            'approved_commissions' => round($approvedCommissions, 4),
            'pending_commissions' => round($pendingCommissions, 4),
            'paid_payouts' => round($paidPayouts, 4),
            'paid_commissions' => round($paidAllocations, 4),
            'reserved_payouts' => round($reservedAllocations, 4),
            'available_balance' => round(max($approvedCommissions - $activeAllocationsAgainstApprovedCommissions, 0), 4),
        ];
    }

    public function requestPayout(AffiliateProfile $profile, float $amount, array $data = []): AffiliatePayout
    {
        return DB::transaction(function () use ($profile, $amount, $data): AffiliatePayout {
            $this->assertPayoutAmountIsAllowed($profile, $amount);

            $payout = AffiliatePayout::query()->create([
                'affiliate_profile_id' => $profile->id,
                'requested_by_customer_id' => $data['requested_by_customer_id'] ?? $profile->customer_id,
                'status' => AffiliatePayout::STATUS_REQUESTED,
                'amount' => round($amount, 4),
                'currency' => $data['currency'] ?? $this->defaultCurrency($profile),
                'payout_method' => $data['payout_method'] ?? $profile->payout_method,
                'payout_reference' => $data['payout_reference'] ?? $this->generatePayoutReference(),
                'requested_at' => now(),
                'notes' => $data['notes'] ?? null,
                'meta' => $data['meta'] ?? null,
            ]);

            $this->allocateApprovedCommissions($payout);

            return $payout->refresh()->load('allocations');
        });
    }

    public function approve(AffiliatePayout $payout, ?int $adminId = null, ?string $adminNotes = null): AffiliatePayout
    {
        return DB::transaction(function () use ($payout, $adminId, $adminNotes): AffiliatePayout {
            $payout = $payout->refresh();

            if ($payout->status !== AffiliatePayout::STATUS_REQUESTED) {
                throw ValidationException::withMessages([
                    'payout' => 'Only requested payouts can be approved.',
                ]);
            }

            $payout->fill([
                'status' => AffiliatePayout::STATUS_APPROVED,
                'processed_by_admin_id' => $adminId,
                'approved_at' => now(),
                'admin_notes' => $adminNotes ?? $payout->admin_notes,
            ])->save();

            return $payout->refresh();
        });
    }

    public function markPaid(AffiliatePayout $payout, ?int $adminId = null, ?string $reference = null): AffiliatePayout
    {
        return DB::transaction(function () use ($payout, $adminId, $reference): AffiliatePayout {
            $payout = $payout->refresh();

            if (! in_array($payout->status, [AffiliatePayout::STATUS_REQUESTED, AffiliatePayout::STATUS_APPROVED], true)) {
                throw ValidationException::withMessages([
                    'payout' => 'Only requested or approved payouts can be marked paid.',
                ]);
            }

            if ($payout->allocations()->where('status', AffiliatePayoutCommissionAllocation::STATUS_RESERVED)->doesntExist()) {
                $this->allocateApprovedCommissions($payout);
            }

            $payout->fill([
                'status' => AffiliatePayout::STATUS_PAID,
                'processed_by_admin_id' => $adminId ?? $payout->processed_by_admin_id,
                'payout_reference' => $reference ?? $payout->payout_reference ?? $this->generatePayoutReference(),
                'approved_at' => $payout->approved_at ?? now(),
                'paid_at' => now(),
            ])->save();

            $payout->allocations()
                ->where('status', AffiliatePayoutCommissionAllocation::STATUS_RESERVED)
                ->update([
                    'status' => AffiliatePayoutCommissionAllocation::STATUS_PAID,
                    'paid_at' => now(),
                    'updated_at' => now(),
                ]);

            $this->syncPaidCommissionStatuses($payout);

            return $payout->refresh()->load('allocations');
        });
    }

    public function reject(AffiliatePayout $payout, ?int $adminId = null, ?string $reason = null): AffiliatePayout
    {
        return DB::transaction(function () use ($payout, $adminId, $reason): AffiliatePayout {
            $payout = $payout->refresh();

            if (! in_array($payout->status, [AffiliatePayout::STATUS_REQUESTED, AffiliatePayout::STATUS_APPROVED], true)) {
                throw ValidationException::withMessages([
                    'payout' => 'Only requested or approved payouts can be rejected.',
                ]);
            }

            $payout->fill([
                'status' => AffiliatePayout::STATUS_REJECTED,
                'processed_by_admin_id' => $adminId,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ])->save();

            $payout->allocations()
                ->where('status', AffiliatePayoutCommissionAllocation::STATUS_RESERVED)
                ->update([
                    'status' => AffiliatePayoutCommissionAllocation::STATUS_RELEASED,
                    'released_at' => now(),
                    'updated_at' => now(),
                ]);

            return $payout->refresh()->load('allocations');
        });
    }

    public function recordPaidPayout(AffiliateProfile $profile, float $amount, array $data = [], ?int $adminId = null): AffiliatePayout
    {
        $payout = $this->requestPayout($profile, $amount, [
            ...$data,
            'requested_by_customer_id' => null,
        ]);

        if (! empty($data['admin_notes'])) {
            $payout->fill(['admin_notes' => $data['admin_notes']])->save();
        }

        return $this->markPaid($payout, $adminId, $data['payout_reference'] ?? null);
    }

    protected function assertPayoutAmountIsAllowed(AffiliateProfile $profile, float $amount): void
    {
        $minimum = $this->affiliateSettingsService->minimumPayoutAmount();
        $balance = $this->balanceFor($profile);

        if ($amount < $minimum) {
            throw ValidationException::withMessages([
                'amount' => "Minimum payout amount is {$minimum}.",
            ]);
        }

        if ($amount > $balance['available_balance']) {
            throw ValidationException::withMessages([
                'amount' => 'Requested payout amount exceeds the available affiliate balance.',
            ]);
        }
    }

    protected function defaultCurrency(AffiliateProfile $profile): ?string
    {
        return AffiliateCommission::query()
            ->where('affiliate_profile_id', $profile->id)
            ->whereNotNull('currency')
            ->latest('id')
            ->value('currency');
    }

    protected function generatePayoutReference(): string
    {
        do {
            $reference = 'AP-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (AffiliatePayout::query()->where('payout_reference', $reference)->exists());

        return $reference;
    }

    protected function allocateApprovedCommissions(AffiliatePayout $payout): void
    {
        $remaining = round((float) $payout->amount - (float) $payout->allocations()->active()->sum('amount'), 4);

        if ($remaining <= 0) {
            return;
        }

        foreach ($this->approvedCommissionsWithAvailableAmounts($payout->affiliateProfile) as $commission) {
            $availableAmount = round((float) $commission->commission_amount - (float) $commission->active_allocated_amount, 4);

            if ($availableAmount <= 0) {
                continue;
            }

            $allocationAmount = round(min($remaining, $availableAmount), 4);

            AffiliatePayoutCommissionAllocation::query()->create([
                'affiliate_payout_id' => $payout->id,
                'affiliate_commission_id' => $commission->id,
                'affiliate_profile_id' => $payout->affiliate_profile_id,
                'status' => AffiliatePayoutCommissionAllocation::STATUS_RESERVED,
                'amount' => $allocationAmount,
            ]);

            $remaining = round($remaining - $allocationAmount, 4);

            if ($remaining <= 0) {
                return;
            }
        }

        throw ValidationException::withMessages([
            'amount' => 'There is not enough approved commission available for this payout.',
        ]);
    }

    protected function approvedCommissionsWithAvailableAmounts(AffiliateProfile $profile)
    {
        return AffiliateCommission::query()
            ->where('affiliate_profile_id', $profile->id)
            ->where('status', AffiliateCommission::STATUS_APPROVED)
            ->select('affiliate_commissions.*')
            ->selectSub(function ($query): void {
                $query
                    ->from('affiliate_payout_commission_allocations')
                    ->selectRaw('COALESCE(SUM(amount), 0)')
                    ->whereColumn('affiliate_payout_commission_allocations.affiliate_commission_id', 'affiliate_commissions.id')
                    ->whereIn('status', [
                        AffiliatePayoutCommissionAllocation::STATUS_RESERVED,
                        AffiliatePayoutCommissionAllocation::STATUS_PAID,
                    ]);
            }, 'active_allocated_amount')
            ->oldest('id')
            ->get();
    }

    protected function syncPaidCommissionStatuses(AffiliatePayout $payout): void
    {
        $commissionIds = $payout->allocations()
            ->pluck('affiliate_commission_id')
            ->unique()
            ->all();

        foreach ($commissionIds as $commissionId) {
            $commission = AffiliateCommission::query()->find($commissionId);

            if (! $commission) {
                continue;
            }

            $paidAmount = (float) AffiliatePayoutCommissionAllocation::query()
                ->where('affiliate_commission_id', $commission->id)
                ->where('status', AffiliatePayoutCommissionAllocation::STATUS_PAID)
                ->sum('amount');

            if ($paidAmount + 0.0001 < (float) $commission->commission_amount) {
                continue;
            }

            $commission->fill([
                'status' => AffiliateCommission::STATUS_PAID,
                'affiliate_payout_id' => $payout->id,
                'paid_at' => now(),
            ])->save();
        }
    }

    protected function reservedAllocationTotal(AffiliateProfile $profile): float
    {
        return (float) AffiliatePayoutCommissionAllocation::query()
            ->where('affiliate_payout_commission_allocations.affiliate_profile_id', $profile->id)
            ->where('affiliate_payout_commission_allocations.status', AffiliatePayoutCommissionAllocation::STATUS_RESERVED)
            ->whereHas('payout', fn ($query) => $query->whereIn('status', [
                AffiliatePayout::STATUS_REQUESTED,
                AffiliatePayout::STATUS_APPROVED,
            ]))
            ->sum('amount');
    }

    protected function paidAllocationTotal(AffiliateProfile $profile): float
    {
        return (float) AffiliatePayoutCommissionAllocation::query()
            ->where('affiliate_profile_id', $profile->id)
            ->where('status', AffiliatePayoutCommissionAllocation::STATUS_PAID)
            ->sum('amount');
    }

    protected function activeAllocationTotalAgainstApprovedCommissions(AffiliateProfile $profile): float
    {
        return (float) AffiliatePayoutCommissionAllocation::query()
            ->join('affiliate_commissions', 'affiliate_commissions.id', '=', 'affiliate_payout_commission_allocations.affiliate_commission_id')
            ->where('affiliate_payout_commission_allocations.affiliate_profile_id', $profile->id)
            ->where('affiliate_commissions.status', AffiliateCommission::STATUS_APPROVED)
            ->whereIn('affiliate_payout_commission_allocations.status', [
                AffiliatePayoutCommissionAllocation::STATUS_RESERVED,
                AffiliatePayoutCommissionAllocation::STATUS_PAID,
            ])
            ->sum('affiliate_payout_commission_allocations.amount');
    }
}
