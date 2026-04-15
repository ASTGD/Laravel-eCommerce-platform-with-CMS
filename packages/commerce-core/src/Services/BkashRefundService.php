<?php

namespace Platform\CommerceCore\Services;

use Platform\CommerceCore\Models\PaymentAttempt;
use Platform\CommerceCore\Models\PaymentRefund;
use Platform\CommerceCore\Payment\AbstractBkashPayment;
use Platform\CommerceCore\Repositories\PaymentRefundRepository;
use Webkul\Sales\Contracts\Refund as RefundContract;

class BkashRefundService
{
    public function __construct(
        protected PaymentRefundRepository $paymentRefundRepository,
        protected BkashAttemptService $attemptService,
    ) {}

    public function requestRefund(RefundContract $refund): PaymentRefund
    {
        /** @var PaymentAttempt $attempt */
        $attempt = PaymentAttempt::query()
            ->where('provider', BkashAttemptService::PROVIDER)
            ->where('order_id', $refund->order_id)
            ->latest('id')
            ->firstOrFail();

        if ($attempt->status !== 'paid') {
            throw new \RuntimeException('This bKash payment is not in a refundable paid state.');
        }

        if (! $attempt->session_key || ! $attempt->gateway_tran_id) {
            throw new \RuntimeException('The bKash payment references are missing for this order.');
        }

        $payment = $this->resolvePayment($attempt->method_code);
        $amount = round((float) $refund->base_grand_total, 2);

        $this->assertRefundableBalance($attempt, $amount);

        $reason = trim((string) request()->input('refund.reason', ''));
        $reason = $reason !== '' ? $reason : sprintf('Refund #%s for order #%s', $refund->increment_id, $refund->order->increment_id);
        $sku = sprintf('order-%s', $refund->order->increment_id);

        $requestPayload = [
            'amount' => $amount,
            'paymentID' => $attempt->session_key,
            'trxID' => $attempt->gateway_tran_id,
            'sku' => $sku,
            'reason' => $reason,
        ];

        $event = $this->attemptService->logEvent($attempt, 'refund_request', $requestPayload);
        $response = $payment->refundTransaction($attempt->session_key, $attempt->gateway_tran_id, $amount, $sku, $reason);
        $status = $this->statusFromRefundResponse($response);

        if ($status === 'failed' || $status === 'invalid') {
            $message = $this->refundErrorMessage($response);

            $this->attemptService->markEventProcessed($event, 'rejected', $message);

            throw new \RuntimeException($message);
        }

        $paymentRefund = $this->paymentRefundRepository->create([
            'payment_attempt_id' => $attempt->id,
            'order_id' => $refund->order_id,
            'refund_id' => $refund->id,
            'provider' => $attempt->provider,
            'method_code' => $attempt->method_code,
            'merchant_tran_id' => $attempt->merchant_tran_id,
            'gateway_tran_id' => $attempt->gateway_tran_id,
            'gateway_refund_ref' => $this->extractRefundReference($response),
            'gateway_bank_tran_id' => $this->extractOriginalTransaction($response) ?: $attempt->gateway_tran_id,
            'requested_amount' => $amount,
            'currency' => $attempt->currency,
            'reason' => $reason,
            'status' => $status,
            'gateway_status' => $this->extractGatewayStatus($response),
            'requested_by_admin_id' => auth('admin')->id(),
            'requested_at' => now(),
            'last_checked_at' => now(),
            'processed_at' => $status === 'refunded' ? now() : null,
            'failed_at' => $status === 'failed' ? now() : null,
            'last_error' => $status === 'failed' ? $this->refundErrorMessage($response) : null,
            'meta' => [
                'refund_increment_id' => $refund->increment_id,
                'payment_id' => $attempt->session_key,
                'sku' => $sku,
            ],
            'last_payload' => $response,
        ]);

        $this->attemptService->markEventProcessed($event, 'processed', sprintf(
            'Refund request accepted with status %s.',
            strtoupper($paymentRefund->status)
        ));

        return $paymentRefund;
    }

    public function statusFromRefundResponse(array $response): string
    {
        $transactionStatus = strtoupper((string) ($response['transactionStatus'] ?? ''));
        $statusMessage = trim((string) ($response['statusMessage'] ?? ''));
        $errorCode = trim((string) ($response['errorCode'] ?? $response['statusCode'] ?? ''));

        if ($transactionStatus === 'COMPLETED' && $this->extractRefundReference($response)) {
            return 'refunded';
        }

        if (in_array($transactionStatus, ['INITIATED', 'PENDING', 'PROCESSING'], true)) {
            return 'pending';
        }

        if ($statusMessage !== '' && strcasecmp($statusMessage, 'Successful') !== 0) {
            return 'failed';
        }

        if ($errorCode !== '' && ! in_array($errorCode, ['0000'], true)) {
            return 'failed';
        }

        return $this->extractRefundReference($response) ? 'pending' : 'invalid';
    }

    public function extractRefundReference(array $response): ?string
    {
        foreach ([
            $response['refundTrxID'] ?? null,
            $response['refundTransactionId'] ?? null,
            $response['refund_ref_id'] ?? null,
        ] as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return null;
    }

    public function extractOriginalTransaction(array $response): ?string
    {
        foreach ([
            $response['originalTrxID'] ?? null,
            $response['trxID'] ?? null,
        ] as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return null;
    }

    public function extractGatewayStatus(array $response): ?string
    {
        foreach ([
            $response['transactionStatus'] ?? null,
            $response['statusMessage'] ?? null,
            $response['statusCode'] ?? null,
        ] as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return strtoupper(trim($candidate));
            }
        }

        return null;
    }

    public function refundErrorMessage(array $response): string
    {
        foreach ([
            $response['statusMessage'] ?? null,
            $response['errorMessage'] ?? null,
            $response['message'] ?? null,
        ] as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '' && strcasecmp(trim($candidate), 'Successful') !== 0) {
                return trim($candidate);
            }
        }

        return 'The bKash refund request was rejected.';
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

    protected function assertRefundableBalance(PaymentAttempt $attempt, float $amount): void
    {
        $reserved = $attempt->refunds()
            ->whereIn('status', ['pending', 'refunded'])
            ->sum('requested_amount');

        if (($reserved + $amount) - (float) $attempt->amount > 0.01) {
            throw new \RuntimeException('The requested refund exceeds the captured bKash payment amount.');
        }
    }
}
