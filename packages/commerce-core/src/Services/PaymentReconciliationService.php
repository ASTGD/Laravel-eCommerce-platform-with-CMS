<?php

namespace Platform\CommerceCore\Services;

use Platform\CommerceCore\Models\PaymentAttempt;

class PaymentReconciliationService
{
    public function __construct(
        protected SslCommerzReconciliationService $sslCommerzReconciliationService,
        protected BkashReconciliationService $bkashReconciliationService,
    ) {}

    public function reconcile(PaymentAttempt $attempt, string $via = 'manual_reconcile'): PaymentAttempt
    {
        return match ($attempt->provider) {
            SslCommerzAttemptService::PROVIDER => $this->sslCommerzReconciliationService->reconcile($attempt, $via),
            BkashAttemptService::PROVIDER => $this->bkashReconciliationService->reconcile($attempt, $via),
            default => throw new \RuntimeException("Payment provider [{$attempt->provider}] does not support reconciliation."),
        };
    }

    public function reconcilePending(?string $provider = null, int $limit = 50, ?int $olderThanMinutes = null): array
    {
        $providers = $provider ? [$provider] : [
            SslCommerzAttemptService::PROVIDER,
            BkashAttemptService::PROVIDER,
        ];

        $result = [
            'processed' => 0,
            'paid' => 0,
            'non_paid' => 0,
            'errors' => 0,
        ];

        foreach ($providers as $providerCode) {
            $providerResult = match ($providerCode) {
                SslCommerzAttemptService::PROVIDER => $this->sslCommerzReconciliationService->reconcilePending($limit, $olderThanMinutes),
                BkashAttemptService::PROVIDER => $this->bkashReconciliationService->reconcilePending($limit, $olderThanMinutes),
                default => throw new \RuntimeException("Payment provider [{$providerCode}] does not support reconciliation."),
            };

            foreach (array_keys($result) as $key) {
                $result[$key] += $providerResult[$key] ?? 0;
            }
        }

        return $result;
    }
}
