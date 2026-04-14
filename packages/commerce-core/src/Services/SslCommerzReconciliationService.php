<?php

namespace Platform\CommerceCore\Services;

use Platform\CommerceCore\Models\PaymentAttempt;
use Platform\CommerceCore\Payment\AbstractSslCommerzPayment;
use Platform\CommerceCore\Support\SslCommerzStatusMapper;

class SslCommerzReconciliationService
{
    public function __construct(
        protected SslCommerzAttemptService $attemptService,
        protected SslCommerzFinalizationService $finalizationService,
        protected SslCommerzStatusMapper $statusMapper,
    ) {}

    public function reconcile(PaymentAttempt $attempt, string $via = 'manual_reconcile'): PaymentAttempt
    {
        $payment = $this->resolvePayment($attempt->method_code);
        $payload = [
            'tran_id' => $attempt->merchant_tran_id,
            'value_a' => $attempt->cart_id,
            'value_b' => $attempt->method_code,
        ];

        $event = $this->attemptService->logEvent($attempt, $via, $payload);

        try {
            $validated = $payment->validateTransaction($payload);
            $status = $this->statusMapper->statusFromValidated($validated);

            $this->recordReconciliation($attempt, $validated, $via);

            if ($status === 'paid') {
                $this->finalizationService->finalizeValidatedAttempt($attempt, $validated, $via);

                $this->attemptService->markEventProcessed($event, 'processed');

                return $attempt->fresh();
            }

            if (! $attempt->finalized_at) {
                $this->updateAttemptOutcome($attempt, $validated, $status);
            }

            $this->attemptService->markEventProcessed($event, 'processed', $this->statusMapper->userMessageForValidated($validated));

            return $attempt->fresh();
        } catch (\Throwable $e) {
            $attempt->refresh();

            $attempt->forceFill([
                'last_reconciled_at' => now(),
                'last_reconciled_status' => $attempt->validation_status ?: strtoupper($attempt->status),
                'last_reconciled_via' => $via,
                'last_reconcile_error' => $e->getMessage(),
            ])->save();

            $this->attemptService->markEventProcessed($event, 'error', $e->getMessage());

            throw $e;
        }
    }

    public function reconcilePending(int $limit = 50, ?int $olderThanMinutes = null): array
    {
        $query = PaymentAttempt::query()
            ->where('provider', SslCommerzAttemptService::PROVIDER)
            ->whereIn('status', ['initiated', 'redirected', 'pending_validation', 'error']);

        if ($olderThanMinutes) {
            $query->where('updated_at', '<=', now()->subMinutes($olderThanMinutes));
        }

        $attempts = $query
            ->orderBy('updated_at')
            ->limit($limit)
            ->get();

        $result = [
            'processed' => 0,
            'paid' => 0,
            'non_paid' => 0,
            'errors' => 0,
        ];

        foreach ($attempts as $attempt) {
            try {
                $attempt = $this->reconcile($attempt, 'scheduled_reconcile');

                $result['processed']++;
                $result[$attempt->status === 'paid' ? 'paid' : 'non_paid']++;
            } catch (\Throwable) {
                $result['processed']++;
                $result['errors']++;
            }
        }

        return $result;
    }

    protected function recordReconciliation(PaymentAttempt $attempt, array $validated, string $via): void
    {
        $attempt->forceFill([
            'last_reconciled_at' => now(),
            'last_reconciled_status' => $this->statusMapper->validationStatus($validated),
            'last_reconciled_via' => $via,
            'last_reconcile_error' => null,
        ])->save();
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

    protected function updateAttemptOutcome(PaymentAttempt $attempt, array $validated, string $status): void
    {
        $meta = $attempt->meta ?? [];
        $meta['validated'] = $validated;

        $attempt->forceFill([
            'gateway_tran_id' => $this->attemptService->extractGatewayTransactionId($validated),
            'status' => $status,
            'validation_status' => $this->statusMapper->validationStatus($validated) ?? strtoupper($status),
            'meta' => $meta,
            'last_payload' => $validated,
        ])->save();
    }
}
