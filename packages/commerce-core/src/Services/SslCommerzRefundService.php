<?php

namespace Platform\CommerceCore\Services;

use Illuminate\Support\Str;
use Platform\CommerceCore\Models\PaymentAttempt;
use Platform\CommerceCore\Models\PaymentRefund;
use Platform\CommerceCore\Payment\AbstractSslCommerzPayment;
use Platform\CommerceCore\Repositories\PaymentRefundRepository;
use Platform\CommerceCore\Support\PaymentMethodRegistry;
use Webkul\Sales\Contracts\Refund as RefundContract;

class SslCommerzRefundService
{
    public function __construct(
        protected PaymentRefundRepository $paymentRefundRepository,
        protected SslCommerzAttemptService $attemptService,
    ) {}

    public function requestRefund(RefundContract $refund): PaymentRefund
    {
        /** @var PaymentAttempt $attempt */
        $attempt = PaymentAttempt::query()
            ->where('provider', SslCommerzAttemptService::PROVIDER)
            ->where('order_id', $refund->order_id)
            ->latest('id')
            ->firstOrFail();

        if ($attempt->status !== 'paid') {
            throw new \RuntimeException('This SSLCommerz payment is not in a refundable paid state.');
        }

        if (! $attempt->gateway_tran_id) {
            throw new \RuntimeException('The SSLCommerz gateway transaction reference is missing for this order.');
        }

        $payment = $this->resolvePayment($attempt->method_code);
        $amount = round((float) $refund->base_grand_total, 2);

        $this->assertRefundableBalance($attempt, $amount);

        $reason = trim((string) request()->input('refund.reason', ''));
        $reason = $reason !== '' ? $reason : sprintf('Refund #%s for order #%s', $refund->increment_id, $refund->order->increment_id);
        $referenceId = $this->makeRefundReference($refund);

        $requestPayload = [
            'bank_tran_id' => $attempt->gateway_tran_id,
            'refund_amount' => $amount,
            'refund_remarks' => $reason,
            'refe_id' => $referenceId,
        ];

        $event = $this->attemptService->logEvent($attempt, 'refund_request', $requestPayload);
        $response = $payment->refundTransaction($attempt->gateway_tran_id, $amount, $reason, $referenceId);
        $status = $this->statusFromRefundResponse($response, true);

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
            'gateway_refund_ref' => $this->extractRefundReference($response) ?: $referenceId,
            'gateway_bank_tran_id' => $this->extractGatewayBankTransaction($response),
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
            ],
            'last_payload' => $response,
        ]);

        $this->attemptService->markEventProcessed($event, 'processed', sprintf(
            'Refund request accepted with status %s.',
            strtoupper($paymentRefund->status)
        ));

        return $paymentRefund;
    }

    public function statusFromRefundResponse(array $response, bool $requestPhase = false): string
    {
        $status = strtoupper((string) ($response['status'] ?? $response['refund_status'] ?? $response['APIConnect'] ?? ''));

        return match ($status) {
            'SUCCESS', 'SUCCESSFUL', 'VALID', 'VALIDATED', 'REFUNDED', 'COMPLETED' => 'refunded',
            'DONE', 'PENDING', 'PROCESSING', 'INITIATED' => $requestPhase ? 'pending' : 'pending',
            'FAILED', 'FAIL', 'ERROR', 'INVALID_REQUEST', 'CANCELLED', 'CANCELED' => 'failed',
            default => $requestPhase && $this->extractRefundReference($response) ? 'pending' : 'invalid',
        };
    }

    public function extractRefundReference(array $response): ?string
    {
        foreach ([
            $response['refund_ref_id'] ?? null,
            $response['refund_refId'] ?? null,
            $response['refund_ref'] ?? null,
            $response['refe_id'] ?? null,
        ] as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return null;
    }

    public function extractGatewayBankTransaction(array $response): ?string
    {
        foreach ([
            $response['bank_tran_id'] ?? null,
            $response['gateway_bank_tran_id'] ?? null,
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
            $response['status'] ?? null,
            $response['refund_status'] ?? null,
            $response['APIConnect'] ?? null,
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
            $response['errorReason'] ?? null,
            $response['failedreason'] ?? null,
            $response['failedReason'] ?? null,
            $response['message'] ?? null,
        ] as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return 'The SSLCommerz refund request was rejected.';
    }

    protected function resolvePayment(string $code): AbstractSslCommerzPayment
    {
        $paymentConfig = config('payment_methods.'.PaymentMethodRegistry::canonicalCode($code));

        if (! $paymentConfig || ! isset($paymentConfig['class'])) {
            throw new \RuntimeException("Payment method [{$code}] is not configured.");
        }

        $payment = app($paymentConfig['class']);

        if (! $payment instanceof AbstractSslCommerzPayment) {
            throw new \RuntimeException("Payment method [{$code}] is not an SSLCommerz payment.");
        }

        return $payment;
    }

    protected function assertRefundableBalance(PaymentAttempt $attempt, float $amount): void
    {
        $reserved = $attempt->refunds()
            ->whereIn('status', ['pending', 'refunded'])
            ->sum('requested_amount');

        if (($reserved + $amount) - (float) $attempt->amount > 0.01) {
            throw new \RuntimeException('The requested refund exceeds the captured SSLCommerz payment amount.');
        }
    }

    protected function makeRefundReference(RefundContract $refund): string
    {
        return sprintf(
            'refund_%s_%s',
            $refund->id,
            Str::upper(Str::random(8))
        );
    }
}
