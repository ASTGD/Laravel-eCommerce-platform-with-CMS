<?php

namespace Platform\CommerceCore\Services;

use Platform\CommerceCore\Models\PaymentAttempt;
use Webkul\Sales\Contracts\Refund as RefundContract;

class PaymentRefundService
{
    public function __construct(
        protected SslCommerzRefundService $sslCommerzRefundService,
        protected BkashRefundService $bkashRefundService,
    ) {}

    public function requestRefund(RefundContract $refund): bool
    {
        $provider = data_get($refund->order?->payment?->additional, 'provider')
            ?: PaymentAttempt::query()->where('order_id', $refund->order_id)->latest('id')->value('provider');

        return match ($provider) {
            SslCommerzAttemptService::PROVIDER => (bool) $this->sslCommerzRefundService->requestRefund($refund),
            BkashAttemptService::PROVIDER => (bool) $this->bkashRefundService->requestRefund($refund),
            default => false,
        };
    }
}
