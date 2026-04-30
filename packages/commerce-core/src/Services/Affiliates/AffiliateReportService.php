<?php

namespace Platform\CommerceCore\Services\Affiliates;

use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Platform\CommerceCore\Models\AffiliateClick;
use Platform\CommerceCore\Models\AffiliateCommission;
use Platform\CommerceCore\Models\AffiliateOrderAttribution;
use Platform\CommerceCore\Models\AffiliatePayout;
use Platform\CommerceCore\Models\AffiliatePayoutCommissionAllocation;
use Platform\CommerceCore\Models\AffiliateProfile;

class AffiliateReportService
{
    public function dashboard(int $days = 30): array
    {
        $summary = $this->summary();
        $series = $this->dailySeries($days);

        return [
            'summary' => $summary,
            'kpis' => $this->kpis($summary),
            'series' => $series,
            'top_affiliates' => $this->topAffiliates(),
            'recent_payouts' => $this->recentPayouts(),
        ];
    }

    public function summary(): array
    {
        $traffic = $this->trafficTotals();
        $attributedOrders = $this->attributedOrderTotals();
        $commissions = $this->commissionTotals();
        $payouts = $this->payoutTotals();
        $balance = $this->globalBalanceTotals();

        return [
            'total_affiliates' => AffiliateProfile::query()->count(),
            'active_affiliates' => AffiliateProfile::query()->where('status', AffiliateProfile::STATUS_ACTIVE)->count(),
            'pending_applications' => AffiliateProfile::query()->where('status', AffiliateProfile::STATUS_PENDING)->count(),
            'total_clicks' => $traffic['total_clicks'],
            'unique_visitors' => $traffic['unique_visitors'],
            'attributed_orders' => $attributedOrders['count'],
            'attributed_sales_total' => $attributedOrders['sales_total'],
            'commissions' => $commissions,
            'payouts' => $payouts,
            'balance' => $balance,
        ];
    }

    public function dailySeries(int $days = 14): array
    {
        $start = now()->subDays($days - 1)->startOfDay();
        $end = now()->endOfDay();
        $labels = collect(CarbonPeriod::create($start, '1 day', $end))
            ->map(fn ($date): string => $date->format('Y-m-d'))
            ->values();

        $clicks = $this->dateCountMap(AffiliateClick::query(), 'clicked_at', $start, $end);
        $orders = $this->dateCountMap(
            AffiliateOrderAttribution::query()->where('status', AffiliateOrderAttribution::STATUS_ATTRIBUTED),
            'attributed_at',
            $start,
            $end,
        );
        $commissions = $this->dateSumMap(
            AffiliateCommission::query()->whereIn('status', [
                AffiliateCommission::STATUS_PENDING,
                AffiliateCommission::STATUS_APPROVED,
                AffiliateCommission::STATUS_PAID,
            ]),
            'created_at',
            'commission_amount',
            $start,
            $end,
        );
        $payoutAndReversal = $this->dailyPayoutAndReversalSeries($days);
        $registrations = $this->dailyRegistrationSeries($days);
        $payouts = array_combine($payoutAndReversal['labels'], $payoutAndReversal['paid_payouts']) ?: [];
        $reversed = array_combine($payoutAndReversal['labels'], $payoutAndReversal['reversed_commissions']) ?: [];
        $registrationCounts = array_combine($registrations['labels'], $registrations['registrations']) ?: [];

        return [
            'labels' => $labels->all(),
            'clicks' => $labels->map(fn (string $date): int => (int) ($clicks[$date] ?? 0))->all(),
            'orders' => $labels->map(fn (string $date): int => (int) ($orders[$date] ?? 0))->all(),
            'commissions' => $labels->map(fn (string $date): float => round((float) ($commissions[$date] ?? 0), 4))->all(),
            'paid_payouts' => $payoutAndReversal['paid_payouts'],
            'reversed_commissions' => $payoutAndReversal['reversed_commissions'],
            'registrations' => $registrations['registrations'],
            'payouts' => $payoutAndReversal['paid_payouts'],
            'reversed' => $payoutAndReversal['reversed_commissions'],
            'rows' => $labels->map(fn (string $date): array => [
                'date' => $date,
                'clicks' => (int) ($clicks[$date] ?? 0),
                'orders' => (int) ($orders[$date] ?? 0),
                'commissions' => round((float) ($commissions[$date] ?? 0), 4),
                'payouts' => round((float) ($payouts[$date] ?? 0), 4),
                'reversed' => round((float) ($reversed[$date] ?? 0), 4),
                'registrations' => (int) ($registrationCounts[$date] ?? 0),
            ])->all(),
        ];
    }

    public function dailyRegistrationSeries(int $days = 14): array
    {
        $start = now()->subDays($days - 1)->startOfDay();
        $end = now()->endOfDay();
        $labels = collect(CarbonPeriod::create($start, '1 day', $end))
            ->map(fn ($date): string => $date->format('Y-m-d'))
            ->values();

        $registrations = $this->dateCountMap(
            AffiliateProfile::query(),
            'created_at',
            $start,
            $end,
        );

        return [
            'labels' => $labels->all(),
            'registrations' => $labels->map(fn (string $date): int => (int) ($registrations[$date] ?? 0))->all(),
        ];
    }

    public function dailyPayoutAndReversalSeries(int $days = 14): array
    {
        $start = now()->subDays($days - 1)->startOfDay();
        $end = now()->endOfDay();
        $labels = collect(CarbonPeriod::create($start, '1 day', $end))
            ->map(fn ($date): string => $date->format('Y-m-d'))
            ->values();

        $paidPayouts = $this->dateSumMap(
            AffiliatePayout::query()->where('status', AffiliatePayout::STATUS_PAID),
            'COALESCE(paid_at, updated_at, created_at)',
            'amount',
            $start,
            $end,
        );
        $reversedCommissions = $this->dateSumMap(
            AffiliateCommission::query()->where('status', AffiliateCommission::STATUS_REVERSED),
            'COALESCE(reversed_at, updated_at, created_at)',
            'commission_amount',
            $start,
            $end,
        );

        return [
            'labels' => $labels->all(),
            'paid_payouts' => $labels->map(fn (string $date): float => round((float) ($paidPayouts[$date] ?? 0), 4))->all(),
            'reversed_commissions' => $labels->map(fn (string $date): float => round((float) ($reversedCommissions[$date] ?? 0), 4))->all(),
        ];
    }

    public function topAffiliates(int $limit = 10)
    {
        return AffiliateProfile::query()
            ->with('customer')
            ->withCount([
                'clicks',
                'attributions' => fn ($query) => $query->where('status', AffiliateOrderAttribution::STATUS_ATTRIBUTED),
            ])
            ->withSum(['commissions as commission_total' => fn ($query) => $query->whereIn('status', [
                AffiliateCommission::STATUS_PENDING,
                AffiliateCommission::STATUS_APPROVED,
                AffiliateCommission::STATUS_PAID,
            ])], 'commission_amount')
            ->orderByDesc('commission_total')
            ->orderByDesc('attributions_count')
            ->limit($limit)
            ->get();
    }

    public function recentPayouts(int $limit = 6)
    {
        return AffiliatePayout::query()
            ->with('affiliateProfile.customer')
            ->latest('requested_at')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    protected function kpis(array $summary): array
    {
        $commissions = $summary['commissions'];
        $payouts = $summary['payouts'];
        $balance = $summary['balance'];
        $totalClicks = (int) $summary['total_clicks'];
        $attributedOrders = (int) $summary['attributed_orders'];

        return [
            'total_clicks' => $totalClicks,
            'unique_visitors' => (int) $summary['unique_visitors'],
            'referred_orders' => $attributedOrders,
            'conversion_rate' => $totalClicks > 0 ? round(($attributedOrders / $totalClicks) * 100, 2) : 0.0,
            'total_commission_earned' => round(
                (float) ($commissions[AffiliateCommission::STATUS_PENDING] ?? 0)
                + (float) ($commissions[AffiliateCommission::STATUS_APPROVED] ?? 0)
                + (float) ($commissions[AffiliateCommission::STATUS_PAID] ?? 0),
                4,
            ),
            'available_balance' => (float) $balance['available_balance'],
            'paid_out' => (float) ($payouts[AffiliatePayout::STATUS_PAID] ?? 0),
            'pending_payout_requests' => AffiliatePayout::query()->where('status', AffiliatePayout::STATUS_REQUESTED)->count(),
            'reversed_commissions' => (float) ($commissions[AffiliateCommission::STATUS_REVERSED] ?? 0),
        ];
    }

    protected function trafficTotals(): array
    {
        $clicks = AffiliateClick::query();
        $uniqueVisitors = (int) (clone $clicks)
            ->selectRaw('COUNT(DISTINCT COALESCE(NULLIF(session_id, ""), NULLIF(ip_address, ""), id)) as aggregate')
            ->value('aggregate');

        return [
            'total_clicks' => (clone $clicks)->count(),
            'unique_visitors' => $uniqueVisitors,
        ];
    }

    protected function attributedOrderTotals(): array
    {
        $query = AffiliateOrderAttribution::query()
            ->where('affiliate_order_attributions.status', AffiliateOrderAttribution::STATUS_ATTRIBUTED);

        $salesTotal = round((float) (clone $query)
            ->join('orders', 'orders.id', '=', 'affiliate_order_attributions.order_id')
            ->sum('orders.base_grand_total'), 4);

        return [
            'count' => (clone $query)->count(),
            'sales_total' => $salesTotal,
        ];
    }

    protected function commissionTotals(): array
    {
        return $this->statusTotals(AffiliateCommission::query(), 'commission_amount', [
            AffiliateCommission::STATUS_PENDING,
            AffiliateCommission::STATUS_APPROVED,
            AffiliateCommission::STATUS_PAID,
            AffiliateCommission::STATUS_REVERSED,
        ]);
    }

    protected function payoutTotals(): array
    {
        return $this->statusTotals(AffiliatePayout::query(), 'amount', [
            AffiliatePayout::STATUS_REQUESTED,
            AffiliatePayout::STATUS_APPROVED,
            AffiliatePayout::STATUS_PAID,
            AffiliatePayout::STATUS_REJECTED,
        ]);
    }

    protected function globalBalanceTotals(): array
    {
        $approvedCommissions = (float) AffiliateCommission::query()
            ->where('status', AffiliateCommission::STATUS_APPROVED)
            ->sum('commission_amount');
        $paidCommissionPool = (float) AffiliateCommission::query()
            ->where('status', AffiliateCommission::STATUS_PAID)
            ->sum('commission_amount');
        $reservedPayouts = (float) DB::table('affiliate_payout_commission_allocations')
            ->join('affiliate_commissions', 'affiliate_commissions.id', '=', 'affiliate_payout_commission_allocations.affiliate_commission_id')
            ->join('affiliate_payouts', 'affiliate_payouts.id', '=', 'affiliate_payout_commission_allocations.affiliate_payout_id')
            ->where('affiliate_payout_commission_allocations.status', AffiliatePayoutCommissionAllocation::STATUS_RESERVED)
            ->where('affiliate_commissions.status', '!=', AffiliateCommission::STATUS_REVERSED)
            ->whereIn('affiliate_payouts.status', [
                AffiliatePayout::STATUS_REQUESTED,
                AffiliatePayout::STATUS_APPROVED,
            ])
            ->sum('affiliate_payout_commission_allocations.amount');
        $paidPayouts = (float) AffiliatePayout::query()
            ->where('status', AffiliatePayout::STATUS_PAID)
            ->sum('amount');

        return [
            'payable_earned' => round($approvedCommissions + $paidCommissionPool, 4),
            'approved_commissions' => round($approvedCommissions, 4),
            'paid_commission_pool' => round($paidCommissionPool, 4),
            'reserved_payouts' => round($reservedPayouts, 4),
            'paid_payouts' => round($paidPayouts, 4),
            'available_balance' => round($approvedCommissions + $paidCommissionPool - $paidPayouts - $reservedPayouts, 4),
        ];
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
            ->whereBetween(DB::raw($dateColumn), [$start, $end])
            ->selectRaw("DATE({$dateColumn}) as metric_date, COALESCE(SUM({$amountColumn}), 0) as aggregate")
            ->groupBy(DB::raw("DATE({$dateColumn})"))
            ->pluck('aggregate', 'metric_date')
            ->all();
    }
}
