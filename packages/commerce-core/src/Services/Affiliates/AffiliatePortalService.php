<?php

namespace Platform\CommerceCore\Services\Affiliates;

use Platform\CommerceCore\Models\AffiliateClick;
use Platform\CommerceCore\Models\AffiliateCommission;
use Platform\CommerceCore\Models\AffiliateOrderAttribution;
use Platform\CommerceCore\Models\AffiliatePayout;
use Platform\CommerceCore\Models\AffiliateProfile;

class AffiliatePortalService
{
    public function __construct(
        protected AffiliatePayoutService $affiliatePayoutService,
        protected AffiliateSettingsService $affiliateSettingsService,
    ) {}

    public function dashboardFor(AffiliateProfile $profile): array
    {
        return [
            'referral' => [
                'code' => $profile->referral_code,
                'url' => $profile->referral_url,
            ],
            'currency' => $this->currencyFor($profile),
            'traffic' => $this->trafficSummary($profile),
            'sales' => $this->salesSummary($profile),
            'commissions' => $this->commissionSummary($profile),
            'balance' => $this->affiliatePayoutService->balanceFor($profile),
            'minimum_payout_amount' => $this->affiliateSettingsService->minimumPayoutAmount(),
            'recent_commissions' => AffiliateCommission::query()
                ->with('order')
                ->where('affiliate_profile_id', $profile->id)
                ->latest('id')
                ->limit(10)
                ->get(),
            'payouts' => AffiliatePayout::query()
                ->where('affiliate_profile_id', $profile->id)
                ->latest('id')
                ->limit(10)
                ->get(),
        ];
    }

    protected function trafficSummary(AffiliateProfile $profile): array
    {
        $clicks = AffiliateClick::query()
            ->where('affiliate_profile_id', $profile->id);

        $latestClick = (clone $clicks)
            ->latest('clicked_at')
            ->first();

        return [
            'total_clicks' => (clone $clicks)->count(),
            'clicks_this_month' => (clone $clicks)
                ->where('clicked_at', '>=', now()->startOfMonth())
                ->count(),
            'latest_click_at' => $latestClick?->clicked_at,
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
        $balance = $this->affiliatePayoutService->balanceFor($profile);
        $totals = AffiliateCommission::query()
            ->where('affiliate_profile_id', $profile->id)
            ->selectRaw('status, COALESCE(SUM(commission_amount), 0) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total) => round((float) $total, 4))
            ->all();

        $pending = (float) ($totals[AffiliateCommission::STATUS_PENDING] ?? 0);
        $approved = (float) ($totals[AffiliateCommission::STATUS_APPROVED] ?? 0);
        $paid = (float) ($balance['paid_commissions'] ?? 0);
        $reversed = (float) ($totals[AffiliateCommission::STATUS_REVERSED] ?? 0);

        return [
            'pending' => $pending,
            'approved' => $approved,
            'paid' => $paid,
            'reversed' => $reversed,
            'total_earned' => round($pending + $approved + (float) ($totals[AffiliateCommission::STATUS_PAID] ?? 0), 4),
        ];
    }

    protected function currencyFor(AffiliateProfile $profile): string
    {
        return (string) (AffiliateCommission::query()
            ->where('affiliate_profile_id', $profile->id)
            ->whereNotNull('currency')
            ->latest('id')
            ->value('currency') ?: core()->getBaseCurrencyCode() ?: 'USD');
    }
}
