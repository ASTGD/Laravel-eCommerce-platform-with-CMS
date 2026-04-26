<?php

namespace Platform\CommerceCore\Listeners;

use Platform\CommerceCore\Services\Affiliates\AffiliateReferralTrackingService;
use Webkul\Sales\Models\Order;

class AttributeAffiliateOrder
{
    public function handle(Order $order): void
    {
        app(AffiliateReferralTrackingService::class)->attributeOrderFromRequest($order, request());
    }
}
