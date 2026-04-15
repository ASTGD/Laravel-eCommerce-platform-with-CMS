<?php

namespace Platform\CommerceCore\Services;

use Platform\CommerceCore\Models\PaymentRefund;

class PaymentRefundStatusService
{
    public function __construct(
        protected SslCommerzRefundStatusService $sslCommerzRefundStatusService,
        protected BkashRefundStatusService $bkashRefundStatusService,
    ) {}

    public function refresh(PaymentRefund $paymentRefund): PaymentRefund
    {
        return match ($paymentRefund->provider ?: $paymentRefund->paymentAttempt?->provider) {
            SslCommerzAttemptService::PROVIDER => $this->sslCommerzRefundStatusService->refresh($paymentRefund),
            BkashAttemptService::PROVIDER => $this->bkashRefundStatusService->refresh($paymentRefund),
            default => throw new \RuntimeException('This payment refund does not support status refresh.'),
        };
    }
}
