<?php

namespace Platform\CommerceCore\Listeners;

use Platform\CommerceCore\Services\Affiliates\AffiliateReferralTrackingService;
use Webkul\Sales\Models\Order;

class ReverseAffiliateCommissionForCanceledOrder
{
    public function handle(Order $order): void
    {
        app(AffiliateReferralTrackingService::class)->reverseOrder($order, 'Order canceled.');
    }
}
