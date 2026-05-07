<?php

namespace Platform\CommerceCore\Services;

use Illuminate\Support\Facades\DB;
use Platform\CommerceCore\Models\PaymentAttempt;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Repositories\OrderRepository;

class PendingPaymentExpiryService
{
    public const DEFAULT_EXPIRE_AFTER_MINUTES = 60;

    public const DEFAULT_RECONCILE_OLDER_THAN_MINUTES = 5;

    protected const EXPIRY_VIA = 'scheduled_expiry';

    protected const EXPIRY_RECONCILE_VIA = 'scheduled_expiry_reconcile';

    protected const UNRESOLVED_STATUSES = [
        'initiated',
        'redirected',
        'pending_validation',
        'error',
    ];

    protected const TERMINAL_NON_PAID_STATUSES = [
        'failed',
        'cancelled',
        'canceled',
        'invalid',
        'non_paid',
    ];

    protected const SUPPORTED_PROVIDERS = [
        SslCommerzAttemptService::PROVIDER,
        BkashAttemptService::PROVIDER,
    ];

    public function __construct(
        protected PaymentReconciliationService $reconciliationService,
        protected OrderRepository $orderRepository,
    ) {}

    public function expirePending(
        ?string $provider = null,
        int $limit = 50,
        ?int $expireAfterMinutes = null,
        ?int $reconcileOlderThanMinutes = null,
    ): array {
        $result = [
            'processed' => 0,
            'reconciled' => 0,
            'paid' => 0,
            'expired_attempts' => 0,
            'cancelled_orders' => 0,
            'skipped' => 0,
            'errors' => 0,
            'disabled' => false,
        ];

        if (! $this->isEnabled()) {
            $result['disabled'] = true;

            return $result;
        }

        $expireAfterMinutes = $this->resolvePositiveMinutes(
            $expireAfterMinutes,
            'sales.payment_methods.pending_payment_expiry.expire_after_minutes',
            self::DEFAULT_EXPIRE_AFTER_MINUTES,
        );

        $reconcileOlderThanMinutes = $this->resolveNonNegativeMinutes(
            $reconcileOlderThanMinutes,
            'sales.payment_methods.pending_payment_expiry.reconcile_older_than_minutes',
            self::DEFAULT_RECONCILE_OLDER_THAN_MINUTES,
        );

        $expiresAt = now()->subMinutes($expireAfterMinutes);
        $providers = $this->providers($provider);

        $attempts = PaymentAttempt::query()
            ->with(['order.payment'])
            ->whereIn('provider', $providers)
            ->whereNull('finalized_at')
            ->whereIn('status', array_merge(self::UNRESOLVED_STATUSES, self::TERMINAL_NON_PAID_STATUSES))
            ->where(function ($query) use ($expiresAt): void {
                $query
                    ->whereIn('status', self::TERMINAL_NON_PAID_STATUSES)
                    ->orWhere('updated_at', '<=', $expiresAt);
            })
            ->where(function ($query): void {
                $query
                    ->whereNull('last_reconciled_via')
                    ->orWhere('last_reconciled_via', '!=', self::EXPIRY_VIA);
            })
            ->orderBy('updated_at')
            ->limit($limit)
            ->get();

        foreach ($attempts as $attempt) {
            $result['processed']++;

            $attempt = $attempt->fresh(['order.payment']);

            if (! $attempt || $attempt->status === 'paid' || $attempt->finalized_at) {
                $result['skipped']++;

                continue;
            }

            $expiredByAge = $attempt->updated_at?->lte($expiresAt) ?? false;
            $terminalNonPaid = $this->isTerminalNonPaid($attempt);

            if (! $terminalNonPaid && $expiredByAge && $this->shouldReconcile($attempt, $reconcileOlderThanMinutes)) {
                try {
                    $attempt = $this->reconciliationService
                        ->reconcile($attempt, self::EXPIRY_RECONCILE_VIA)
                        ->fresh(['order.payment']);

                    $result['reconciled']++;
                } catch (\Throwable $e) {
                    $this->recordExpiryError($attempt, $e);

                    $result['errors']++;

                    continue;
                }

                if (! $attempt || $attempt->status === 'paid' || $attempt->finalized_at) {
                    $result['paid']++;

                    continue;
                }

                $terminalNonPaid = $this->isTerminalNonPaid($attempt);
            }

            if (! $terminalNonPaid && ! $expiredByAge) {
                $result['skipped']++;

                continue;
            }

            try {
                DB::transaction(function () use ($attempt, $terminalNonPaid, $expireAfterMinutes, &$result): void {
                    $attempt = PaymentAttempt::query()
                        ->with(['order.payment'])
                        ->whereKey($attempt->id)
                        ->lockForUpdate()
                        ->first();

                    if (! $attempt || $attempt->status === 'paid' || $attempt->finalized_at) {
                        $result['skipped']++;

                        return;
                    }

                    if ($this->cancelLinkedPendingPaymentOrder($attempt)) {
                        $result['cancelled_orders']++;
                    }

                    $this->markAttemptHandled($attempt, $terminalNonPaid, $expireAfterMinutes);

                    $result['expired_attempts']++;
                }, 3);
            } catch (\Throwable $e) {
                $this->recordExpiryError($attempt, $e);

                $result['errors']++;
            }
        }

        return $result;
    }

    protected function isEnabled(): bool
    {
        $value = core()->getConfigData('sales.payment_methods.pending_payment_expiry.active');

        if ($value === null || $value === '') {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    protected function resolvePositiveMinutes(?int $value, string $configKey, int $default): int
    {
        $minutes = $value ?? (int) core()->getConfigData($configKey);

        return $minutes > 0 ? $minutes : $default;
    }

    protected function resolveNonNegativeMinutes(?int $value, string $configKey, int $default): int
    {
        $minutes = $value ?? core()->getConfigData($configKey);

        if ($minutes === null || $minutes === '') {
            return $default;
        }

        $minutes = (int) $minutes;

        return $minutes >= 0 ? $minutes : $default;
    }

    protected function providers(?string $provider): array
    {
        if ($provider === null || $provider === '') {
            return self::SUPPORTED_PROVIDERS;
        }

        if (! in_array($provider, self::SUPPORTED_PROVIDERS, true)) {
            throw new \RuntimeException("Payment provider [{$provider}] does not support pending-payment expiry.");
        }

        return [$provider];
    }

    protected function isTerminalNonPaid(PaymentAttempt $attempt): bool
    {
        return in_array($attempt->status, self::TERMINAL_NON_PAID_STATUSES, true);
    }

    protected function shouldReconcile(PaymentAttempt $attempt, int $reconcileOlderThanMinutes): bool
    {
        if ($reconcileOlderThanMinutes <= 0) {
            return true;
        }

        if (! $attempt->last_reconciled_at) {
            return true;
        }

        return $attempt->last_reconciled_at->lte(now()->subMinutes($reconcileOlderThanMinutes));
    }

    protected function cancelLinkedPendingPaymentOrder(PaymentAttempt $attempt): bool
    {
        $order = $attempt->order;

        if (! $order || $order->status !== Order::STATUS_PENDING_PAYMENT) {
            return false;
        }

        if ($order->payment?->method === 'cashondelivery') {
            return false;
        }

        if (! $order->canCancel()) {
            return false;
        }

        return (bool) $this->orderRepository->cancel($order);
    }

    protected function markAttemptHandled(PaymentAttempt $attempt, bool $terminalNonPaid, int $expireAfterMinutes): void
    {
        $meta = $attempt->meta ?? [];
        $meta['pending_payment_expiry'] = [
            'handled_at' => now()->toIso8601String(),
            'expire_after_minutes' => $expireAfterMinutes,
            'previous_status' => $attempt->status,
            'reason' => $terminalNonPaid ? 'terminal_non_paid' : 'expired',
        ];

        $payload = [
            'last_reconciled_at' => now(),
            'last_reconciled_status' => $terminalNonPaid ? strtoupper((string) $attempt->status) : 'EXPIRED',
            'last_reconciled_via' => self::EXPIRY_VIA,
            'last_reconcile_error' => null,
            'validation_status' => $terminalNonPaid ? $attempt->validation_status : 'EXPIRED',
            'meta' => $meta,
        ];

        if (! $terminalNonPaid) {
            $payload['status'] = 'expired';
        }

        $attempt->forceFill($payload)->save();
    }

    protected function recordExpiryError(PaymentAttempt $attempt, \Throwable $e): void
    {
        $attempt->refresh();

        $attempt->forceFill([
            'last_reconciled_at' => now(),
            'last_reconciled_status' => $attempt->validation_status ?: strtoupper((string) $attempt->status),
            'last_reconciled_via' => self::EXPIRY_RECONCILE_VIA,
            'last_reconcile_error' => $e->getMessage(),
        ])->save();
    }
}
