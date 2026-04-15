<?php

namespace Platform\CommerceCore\Services;

use Platform\CommerceCore\Models\PaymentRefund;
use Platform\CommerceCore\Payment\AbstractBkashPayment;

class BkashRefundStatusService
{
    public function __construct(
        protected BkashAttemptService $attemptService,
        protected BkashRefundService $refundService,
    ) {}

    public function refresh(PaymentRefund $paymentRefund): PaymentRefund
    {
        $paymentRefund->loadMissing('paymentAttempt');

        $attempt = $paymentRefund->paymentAttempt;

        if (! $attempt) {
            throw new \RuntimeException('The payment attempt for this refund could not be found.');
        }

        if (! $attempt->session_key || ! $attempt->gateway_tran_id) {
            throw new \RuntimeException('The bKash payment references are missing for this refund.');
        }

        $payment = $this->resolvePayment($attempt->method_code);
        $requestPayload = [
            'paymentID' => $attempt->session_key,
            'trxID' => $attempt->gateway_tran_id,
        ];

        $event = $this->attemptService->logEvent($attempt, 'refund_status_check', $requestPayload);
        $response = $payment->queryRefundStatus($attempt->session_key, $attempt->gateway_tran_id);
        $status = $this->refundService->statusFromRefundResponse($response);

        $paymentRefund->forceFill([
            'status' => $status,
            'gateway_status' => $this->refundService->extractGatewayStatus($response),
            'gateway_refund_ref' => $this->refundService->extractRefundReference($response) ?: $paymentRefund->gateway_refund_ref,
            'gateway_bank_tran_id' => $this->refundService->extractOriginalTransaction($response) ?: $paymentRefund->gateway_bank_tran_id,
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

    protected function resolvePayment(string $code): AbstractBkashPayment
    {
        $paymentConfig = config('payment_methods.'.$code);

        if (! $paymentConfig || ! isset($paymentConfig['class'])) {
            throw new \RuntimeException("Payment method [{$code}] is not configured.");
        }

        $payment = app($paymentConfig['class']);

        if (! $payment instanceof AbstractBkashPayment) {
            throw new \RuntimeException("Payment method [{$code}] is not a direct bKash payment.");
        }

        return $payment;
    }
}
