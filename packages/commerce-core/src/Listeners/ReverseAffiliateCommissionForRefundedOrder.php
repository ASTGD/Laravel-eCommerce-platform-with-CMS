<?php

namespace Platform\CommerceCore\Listeners;

use Platform\CommerceCore\Services\Affiliates\AffiliateReferralTrackingService;

class ReverseAffiliateCommissionForRefundedOrder
{
    public function handle($refund): void
    {
        $order = $refund->order ?? null;

        if (! $order) {
            return;
        }

        app(AffiliateReferralTrackingService::class)->reverseOrder($order, 'Order refunded.');
    }
}
