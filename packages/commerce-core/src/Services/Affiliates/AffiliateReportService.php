<?php

namespace Platform\CommerceCore\Services\Affiliates;

use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Platform\CommerceCore\Models\AffiliateClick;
use Platform\CommerceCore\Models\AffiliateCommission;
use Platform\CommerceCore\Models\AffiliateOrderAttribution;
use Platform\CommerceCore\Models\AffiliatePayout;
use Platform\CommerceCore\Models\AffiliateProfile;

class AffiliateReportService
{
    public function summary(): array
    {
        return [
            'total_affiliates' => AffiliateProfile::query()->count(),
            'active_affiliates' => AffiliateProfile::query()->where('status', AffiliateProfile::STATUS_ACTIVE)->count(),
            'pending_applications' => AffiliateProfile::query()->where('status', AffiliateProfile::STATUS_PENDING)->count(),
            'total_clicks' => AffiliateClick::query()->count(),
            'attributed_orders' => AffiliateOrderAttribution::query()
                ->where('status', AffiliateOrderAttribution::STATUS_ATTRIBUTED)
                ->count(),
            'attributed_sales_total' => $this->attributedSalesTotal(),
            'commissions' => $this->commissionTotals(),
            'payouts' => $this->payoutTotals(),
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

        return [
            'labels' => $labels->all(),
            'clicks' => $labels->map(fn (string $date): int => (int) ($clicks[$date] ?? 0))->all(),
            'orders' => $labels->map(fn (string $date): int => (int) ($orders[$date] ?? 0))->all(),
            'commissions' => $labels->map(fn (string $date): float => round((float) ($commissions[$date] ?? 0), 4))->all(),
        ];
    }

    public function topAffiliates(int $limit = 10)
    {
        return AffiliateProfile::query()
            ->with('customer')
            ->withCount(['clicks', 'attributions'])
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

    protected function attributedSalesTotal(): float
    {
        return round((float) AffiliateOrderAttribution::query()
            ->join('orders', 'orders.id', '=', 'affiliate_order_attributions.order_id')
            ->where('affiliate_order_attributions.status', AffiliateOrderAttribution::STATUS_ATTRIBUTED)
            ->sum('orders.base_grand_total'), 4);
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
}
