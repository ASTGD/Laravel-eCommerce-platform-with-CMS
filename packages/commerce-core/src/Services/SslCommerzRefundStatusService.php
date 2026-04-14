<?php

namespace Platform\CommerceCore\Services;

use Platform\CommerceCore\Models\PaymentRefund;
use Platform\CommerceCore\Payment\AbstractSslCommerzPayment;

class SslCommerzRefundStatusService
{
    public function __construct(
        protected SslCommerzAttemptService $attemptService,
        protected SslCommerzRefundService $refundService,
    ) {}

    public function refresh(PaymentRefund $paymentRefund): PaymentRefund
    {
        $paymentRefund->loadMissing('paymentAttempt');

        $attempt = $paymentRefund->paymentAttempt;

        if (! $attempt) {
            throw new \RuntimeException('The payment attempt for this refund could not be found.');
        }

        if (! $paymentRefund->gateway_refund_ref) {
            throw new \RuntimeException('The gateway refund reference is missing for this payment refund.');
        }

        $payment = $this->resolvePayment($attempt->method_code);
        $requestPayload = [
            'refund_ref_id' => $paymentRefund->gateway_refund_ref,
        ];

        $event = $this->attemptService->logEvent($attempt, 'refund_status_check', $requestPayload);
        $response = $payment->queryRefundStatus($paymentRefund->gateway_refund_ref);
        $status = $this->refundService->statusFromRefundResponse($response);

        $paymentRefund->forceFill([
            'status' => $status,
            'gateway_status' => $this->refundService->extractGatewayStatus($response),
            'gateway_bank_tran_id' => $this->refundService->extractGatewayBankTransaction($response) ?: $paymentRefund->gateway_bank_tran_id,
            'last_checked_at' => now(),
            'processed_at' => $status === 'refunded' ? ($paymentRefund->processed_at ?: now()) : $paymentRefund->processed_at,
            'failed_at' => $status === 'failed' ? now() : $paymentRefund->failed_at,
            'last_error' => $status === 'failed' ? $this->refundService->refundErrorMessage($response) : null,
            'last_payload' => $response,
        ])->save();

        $this->attemptService->markEventProcessed($event, 'processed', sprintf(
            'Refund status refreshed to %s.',
            strtoupper($paymentRefund->status)
        ));

        return $paymentRefund->fresh();
    }

    protected function resolvePayment(string $code): AbstractSslCommerzPayment
    {
        $paymentConfig = config('payment_methods.'.$code);

        if (! $paymentConfig || ! isset($paymentConfig['class'])) {
            throw new \RuntimeException("Payment method [{$code}] is not configured.");
        }

        $payment = app($paymentConfig['class']);

        if (! $payment instanceof AbstractSslCommerzPayment) {
            throw new \RuntimeException("Payment method [{$code}] is not an SSLCOMMERZ payment.");
        }

        return $payment;
    }
}
