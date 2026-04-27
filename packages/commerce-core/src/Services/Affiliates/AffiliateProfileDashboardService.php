<?php

namespace Platform\CommerceCore\Services\Affiliates;

use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Platform\CommerceCore\Models\AffiliateClick;
use Platform\CommerceCore\Models\AffiliateCommission;
use Platform\CommerceCore\Models\AffiliateOrderAttribution;
use Platform\CommerceCore\Models\AffiliatePayout;
use Platform\CommerceCore\Models\AffiliateProfile;

class AffiliateProfileDashboardService
{
    public function __construct(
        protected AffiliatePayoutService $affiliatePayoutService,
        protected AffiliateSettingsService $affiliateSettingsService,
        protected AffiliateReferralLinkService $affiliateReferralLinkService,
    ) {}

    public function build(AffiliateProfile $profile, array $filters = []): array
    {
        $profile->loadMissing(['customer', 'approvedBy', 'rejectedBy', 'suspendedBy']);

        $balance = $this->affiliatePayoutService->balanceFor($profile);
        $commissionSummary = $this->commissionSummary($profile);
        $payoutSummary = $this->payoutSummary($profile);
        $trafficSummary = $this->trafficSummary($profile);
        $salesSummary = $this->salesSummary($profile);

        return [
            'currency' => $this->currencyFor($profile),
            'identity' => $this->identity($profile),
            'referral' => $this->referral($profile),
            'settings' => [
                'commission_rule' => $this->commissionRuleSummary(),
                'cookie_window_days' => $this->affiliateSettingsService->cookieWindowDays(),
                'minimum_payout_amount' => $this->affiliateSettingsService->minimumPayoutAmount(),
            ],
            'balance' => $balance,
            'traffic_summary' => $trafficSummary,
            'sales_summary' => $salesSummary,
            'commission_summary' => $commissionSummary,
            'payout_summary' => $payoutSummary,
            'kpis' => $this->kpis($trafficSummary, $salesSummary, $commissionSummary, $payoutSummary, $balance),
            'trend' => $this->trend($profile),
            'recent_orders' => $this->recentReferredOrders($profile),
            'commission_rows' => $this->commissionRows($profile, Arr::get($filters, 'commissions', [])),
            'payout_rows' => $this->payoutRows($profile),
            'traffic_rows' => $this->trafficRows($profile),
            'latest_payout' => $this->latestPayout($profile),
            'latest_withdrawal' => $this->latestWithdrawal($profile),
            'activity' => $this->activity($profile),
        ];
    }

    protected function identity(AffiliateProfile $profile): array
    {
        $name = trim(($profile->customer?->first_name ?? '').' '.($profile->customer?->last_name ?? ''));

        return [
            'name' => $name !== '' ? $name : 'Customer #'.$profile->customer_id,
            'initials' => $this->initials($name !== '' ? $name : 'Affiliate'),
            'email' => $profile->customer?->email,
            'phone' => $profile->customer?->phone,
            'customer_id' => $profile->customer_id,
            'customer_created_at' => $profile->customer?->created_at,
            'joined_at' => $profile->created_at,
            'application_source' => $profile->application_source ?: 'customer_portal',
        ];
    }

    protected function referral(AffiliateProfile $profile): array
    {
        return [
            'code' => $profile->referral_code,
            'url' => $this->affiliateReferralLinkService->build($profile),
            'parameter' => $this->affiliateSettingsService->referralParameter(),
        ];
    }

    protected function kpis(array $traffic, array $sales, array $commissions, array $payouts, array $balance): array
    {
        $totalClicks = (int) $traffic['total_clicks'];
        $orders = (int) $sales['attributed_orders'];

        return [
            'total_clicks' => $totalClicks,
            'referred_orders' => $orders,
            'conversion_rate' => $totalClicks > 0 ? round(($orders / $totalClicks) * 100, 2) : 0.0,
            'total_commission_earned' => round($commissions[AffiliateCommission::STATUS_PENDING] + $commissions[AffiliateCommission::STATUS_APPROVED] + $commissions[AffiliateCommission::STATUS_PAID], 4),
            'available_balance' => (float) $balance['available_balance'],
            'total_paid_out' => (float) $payouts[AffiliatePayout::STATUS_PAID],
            'pending_withdrawals' => (int) $payouts['requested_count'],
        ];
    }

    protected function trafficSummary(AffiliateProfile $profile): array
    {
        $clicks = AffiliateClick::query()->where('affiliate_profile_id', $profile->id);
        $totalClicks = (clone $clicks)->count();
        $uniqueVisitors = (int) (clone $clicks)
            ->selectRaw('COUNT(DISTINCT COALESCE(NULLIF(session_id, ""), NULLIF(ip_address, ""), id)) as aggregate')
            ->value('aggregate');

        return [
            'total_clicks' => $totalClicks,
            'recent_clicks' => (clone $clicks)->where('clicked_at', '>=', now()->subDays(30))->count(),
            'unique_visitors' => $uniqueVisitors,
        ];
    }

    protected function salesSummary(AffiliateProfile $profile): array
    {
        $attributions = AffiliateOrderAttribution::query()
            ->where('affiliate_order_attributions.affiliate_profile_id', $profile->id)
            ->where('affiliate_order_attributions.status', AffiliateOrderAttribution::STATUS_ATTRIBUTED);

        $totals = (clone $attributions)
            ->join('orders', 'orders.id', '=', 'affiliate_order_attributions.order_id')
            ->selectRaw('COUNT(DISTINCT affiliate_order_attributions.order_id) as attributed_orders, COALESCE(SUM(orders.base_grand_total), 0) as attributed_sales_total')
            ->first();

        return [
            'attributed_orders' => (int) ($totals?->attributed_orders ?? 0),
            'attributed_sales_total' => round((float) ($totals?->attributed_sales_total ?? 0), 4),
        ];
    }

    protected function commissionSummary(AffiliateProfile $profile): array
    {
        return $this->statusTotals(AffiliateCommission::query()->where('affiliate_profile_id', $profile->id), 'commission_amount', [
            AffiliateCommission::STATUS_PENDING,
            AffiliateCommission::STATUS_APPROVED,
            AffiliateCommission::STATUS_PAID,
            AffiliateCommission::STATUS_REVERSED,
        ]);
    }

    protected function payoutSummary(AffiliateProfile $profile): array
    {
        $totals = $this->statusTotals(AffiliatePayout::query()->where('affiliate_profile_id', $profile->id), 'amount', [
            AffiliatePayout::STATUS_REQUESTED,
            AffiliatePayout::STATUS_APPROVED,
            AffiliatePayout::STATUS_PAID,
            AffiliatePayout::STATUS_REJECTED,
        ]);

        $totals['requested_count'] = AffiliatePayout::query()
            ->where('affiliate_profile_id', $profile->id)
            ->where('status', AffiliatePayout::STATUS_REQUESTED)
            ->count();

        return $totals;
    }

    protected function trend(AffiliateProfile $profile, int $days = 14): array
    {
        $start = now()->subDays($days - 1)->startOfDay();
        $end = now()->endOfDay();
        $labels = collect(CarbonPeriod::create($start, '1 day', $end))
            ->map(fn ($date): string => $date->format('Y-m-d'))
            ->values();

        $clicks = $this->dateCountMap(
            AffiliateClick::query()->where('affiliate_profile_id', $profile->id),
            'clicked_at',
            $start,
            $end,
        );
        $orders = $this->dateCountMap(
            AffiliateOrderAttribution::query()
                ->where('affiliate_profile_id', $profile->id)
                ->where('status', AffiliateOrderAttribution::STATUS_ATTRIBUTED),
            'attributed_at',
            $start,
            $end,
        );
        $commissions = $this->dateSumMap(
            AffiliateCommission::query()
                ->where('affiliate_profile_id', $profile->id)
                ->whereIn('status', [
                    AffiliateCommission::STATUS_PENDING,
                    AffiliateCommission::STATUS_APPROVED,
                    AffiliateCommission::STATUS_PAID,
                ]),
            'created_at',
            'commission_amount',
            $start,
            $end,
        );

        return [
            'labels' => $labels->all(),
            'max_clicks' => max(1, (int) collect($clicks)->max()),
            'max_orders' => max(1, (int) collect($orders)->max()),
            'max_commissions' => max(1, (float) collect($commissions)->max()),
            'rows' => $labels->map(fn (string $date): array => [
                'date' => $date,
                'clicks' => (int) ($clicks[$date] ?? 0),
                'orders' => (int) ($orders[$date] ?? 0),
                'commissions' => round((float) ($commissions[$date] ?? 0), 4),
            ])->all(),
        ];
    }

    protected function recentReferredOrders(AffiliateProfile $profile): Collection
    {
        $attributions = AffiliateOrderAttribution::query()
            ->with('order')
            ->where('affiliate_profile_id', $profile->id)
            ->latest('attributed_at')
            ->limit(8)
            ->get();

        $commissions = AffiliateCommission::query()
            ->where('affiliate_profile_id', $profile->id)
            ->whereIn('order_id', $attributions->pluck('order_id')->filter()->all())
            ->get()
            ->keyBy('order_id');

        return $attributions->map(fn (AffiliateOrderAttribution $attribution): array => [
            'attribution' => $attribution,
            'order' => $attribution->order,
            'commission' => $commissions->get($attribution->order_id),
        ]);
    }

    protected function commissionRows(AffiliateProfile $profile, array $filters): Collection
    {
        return AffiliateCommission::query()
            ->with(['order', 'payoutAllocations.payout'])
            ->where('affiliate_profile_id', $profile->id)
            ->when(filled(Arr::get($filters, 'status')), fn (Builder $query) => $query->where('status', Arr::get($filters, 'status')))
            ->when(filled(Arr::get($filters, 'date_from')), fn (Builder $query) => $query->whereDate('created_at', '>=', Arr::get($filters, 'date_from')))
            ->when(filled(Arr::get($filters, 'date_to')), fn (Builder $query) => $query->whereDate('created_at', '<=', Arr::get($filters, 'date_to')))
            ->when(filled(Arr::get($filters, 'order')), function (Builder $query) use ($filters): void {
                $search = trim((string) Arr::get($filters, 'order'));

                $query->whereHas('order', function (Builder $orderQuery) use ($search): void {
                    $orderQuery->where('increment_id', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->limit(50)
            ->get();
    }

    protected function payoutRows(AffiliateProfile $profile): Collection
    {
        return AffiliatePayout::query()
            ->where('affiliate_profile_id', $profile->id)
            ->latest('id')
            ->limit(50)
            ->get();
    }

    protected function trafficRows(AffiliateProfile $profile): Collection
    {
        return AffiliateClick::query()
            ->with(['attributions.order'])
            ->where('affiliate_profile_id', $profile->id)
            ->latest('clicked_at')
            ->limit(50)
            ->get();
    }

    protected function latestPayout(AffiliateProfile $profile): ?AffiliatePayout
    {
        return AffiliatePayout::query()
            ->where('affiliate_profile_id', $profile->id)
            ->latest('id')
            ->first();
    }

    protected function latestWithdrawal(AffiliateProfile $profile): ?AffiliatePayout
    {
        return AffiliatePayout::query()
            ->where('affiliate_profile_id', $profile->id)
            ->whereNotNull('requested_by_customer_id')
            ->latest('id')
            ->first();
    }

    protected function activity(AffiliateProfile $profile): Collection
    {
        $entries = collect();

        $entries->push($this->activityEntry('Affiliate profile created', $profile->created_at, $this->sourceLabel($profile->application_source)));

        if ($profile->approved_at) {
            $entries->push($this->activityEntry('Affiliate approved', $profile->approved_at, $profile->approvedBy?->name ?: $this->adminActor($profile->approved_by_admin_id)));
        }

        if ($profile->rejected_at) {
            $entries->push($this->activityEntry('Affiliate rejected', $profile->rejected_at, $profile->rejectedBy?->name ?: $this->adminActor($profile->rejected_by_admin_id), $profile->rejection_reason));
        }

        if ($profile->suspended_at) {
            $entries->push($this->activityEntry('Affiliate suspended', $profile->suspended_at, $profile->suspendedBy?->name ?: $this->adminActor($profile->suspended_by_admin_id), $profile->suspension_reason));
        }

        if ($profile->reactivated_at) {
            $entries->push($this->activityEntry('Affiliate reactivated', $profile->reactivated_at, 'Admin'));
        }

        $this->payoutRows($profile)->each(function (AffiliatePayout $payout) use ($entries): void {
            $entries->push($this->activityEntry('Payout '.$payout->status_label, $payout->paid_at ?: $payout->approved_at ?: $payout->requested_at ?: $payout->created_at, $payout->requested_by_customer_id ? 'Affiliate' : $this->adminActor($payout->processed_by_admin_id), $payout->payout_reference));
        });

        return $entries
            ->filter(fn (array $entry): bool => filled($entry['timestamp']))
            ->sortByDesc('timestamp')
            ->values()
            ->take(30);
    }

    protected function statusTotals($query, string $amountColumn, array $statuses): array
    {
        $totals = $query
            ->selectRaw("status, COALESCE(SUM({$amountColumn}), 0) as total")
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total): float => round((float) $total, 4))
            ->all();

        return collect($statuses)
            ->mapWithKeys(fn (string $status): array => [$status => (float) ($totals[$status] ?? 0)])
            ->all();
    }

    protected function dateCountMap($query, string $dateColumn, $start, $end): array
    {
        return $query
            ->whereBetween($dateColumn, [$start, $end])
            ->selectRaw("DATE({$dateColumn}) as metric_date, COUNT(*) as aggregate")
            ->groupBy(DB::raw("DATE({$dateColumn})"))
            ->pluck('aggregate', 'metric_date')
            ->all();
    }

    protected function dateSumMap($query, string $dateColumn, string $amountColumn, $start, $end): array
    {
        return $query
            ->whereBetween($dateColumn, [$start, $end])
            ->selectRaw("DATE({$dateColumn}) as metric_date, COALESCE(SUM({$amountColumn}), 0) as aggregate")
            ->groupBy(DB::raw("DATE({$dateColumn})"))
            ->pluck('aggregate', 'metric_date')
            ->all();
    }

    protected function commissionRuleSummary(): string
    {
        $rule = $this->affiliateSettingsService->defaultCommission();
        $value = (float) ($rule['value'] ?? 0);

        return ($rule['type'] ?? 'percentage') === 'percentage'
            ? rtrim(rtrim(number_format($value, 2), '0'), '.').'% per order'
            : core()->formatPrice($value, core()->getBaseCurrencyCode()).' per order';
    }

    protected function currencyFor(AffiliateProfile $profile): string
    {
        return (string) (AffiliateCommission::query()
            ->where('affiliate_profile_id', $profile->id)
            ->whereNotNull('currency')
            ->latest('id')
            ->value('currency') ?: core()->getBaseCurrencyCode() ?: 'USD');
    }

    protected function initials(string $name): string
    {
        return collect(explode(' ', trim($name)))
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_substr($part, 0, 1))
            ->implode('');
    }

    protected function activityEntry(string $title, mixed $timestamp, ?string $actor = null, ?string $note = null): array
    {
        return [
            'title' => $title,
            'timestamp' => $timestamp ? now()->parse($timestamp) : null,
            'actor' => $actor ?: 'System',
            'note' => $note,
        ];
    }

    protected function sourceLabel(?string $source): string
    {
        return match ($source) {
            'admin_created' => 'Admin',
            'customer_portal' => 'Affiliate',
            default => 'System',
        };
    }

    protected function adminActor(mixed $adminId): string
    {
        return filled($adminId) ? 'Admin #'.$adminId : 'Admin';
    }
}
