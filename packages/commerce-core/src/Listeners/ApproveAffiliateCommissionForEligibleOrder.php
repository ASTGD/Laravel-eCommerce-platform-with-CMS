<?php

namespace Platform\CommerceCore\Listeners;

use Platform\CommerceCore\Services\Affiliates\AffiliateCommissionService;
use Webkul\Sales\Models\Order;

class ApproveAffiliateCommissionForEligibleOrder
{
    public function handle(Order $order): void
    {
        app(AffiliateCommissionService::class)->handleOrderEligibilityForCommission($order);
    }
}
