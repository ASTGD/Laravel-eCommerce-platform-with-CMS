<?php

namespace Platform\CommerceCore\Services;

use Illuminate\Support\Arr;
use Platform\CommerceCore\Models\PaymentAttempt;
use Platform\CommerceCore\Models\PaymentGatewayEvent;
use Platform\CommerceCore\Payment\AbstractSslCommerzPayment;
use Platform\CommerceCore\Repositories\PaymentAttemptRepository;
use Platform\CommerceCore\Repositories\PaymentGatewayEventRepository;
use Platform\CommerceCore\Support\SslCommerzStatusMapper;
use Webkul\Checkout\Repositories\CartRepository;

class SslCommerzAttemptService
{
    public const PROVIDER = 'sslcommerz';

    public function __construct(
        protected CartRepository $cartRepository,
        protected PaymentAttemptRepository $attemptRepository,
        protected PaymentGatewayEventRepository $eventRepository,
        protected SslCommerzStatusMapper $statusMapper,
    ) {}

    public function buildPaymentSnapshot(PaymentAttempt $attempt, array $validated = []): array
    {
        return [
            'provider' => self::PROVIDER,
            'method_code' => $attempt->method_code,
            'attempt_id' => $attempt->id,
            'merchant_transaction_id' => $attempt->merchant_tran_id,
            'gateway_transaction_id' => $attempt->gateway_tran_id,
            'session_key' => $attempt->session_key,
            'status' => $attempt->status,
            'validation_status' => $attempt->validation_status,
            'finalized_via' => $attempt->finalized_via,
            'validated' => $validated ?: ($attempt->meta['validated'] ?? null),
        ];
    }

    public function createInitiatedAttempt(AbstractSslCommerzPayment $payment, $cart, string $merchantTranId): PaymentAttempt
    {
        return $this->attemptRepository->create([
            'cart_id' => $cart->id,
            'customer_id' => $cart->customer_id,
            'provider' => self::PROVIDER,
            'method_code' => $payment->getCode(),
            'merchant_tran_id' => $merchantTranId,
            'currency' => strtoupper($cart->base_currency_code ?? core()->getBaseCurrencyCode()),
            'amount' => (float) $cart->base_grand_total,
            'status' => 'initiated',
            'meta' => [
                'payment_title' => $payment->getTitle(),
            ],
        ]);
    }

    public function findAttempt(AbstractSslCommerzPayment $payment, array $payload): ?PaymentAttempt
    {
        $merchantTranId = $this->extractMerchantTransactionId($payload);

        if ($merchantTranId) {
            return PaymentAttempt::query()
                ->where('provider', self::PROVIDER)
                ->where('merchant_tran_id', $merchantTranId)
                ->first();
        }

        $sessionKey = $this->extractSessionKey($payload);

        if ($sessionKey) {
            return PaymentAttempt::query()
                ->where('provider', self::PROVIDER)
                ->where('session_key', $sessionKey)
                ->first();
        }

        $cartId = $this->extractCartId($payload);

        if (! $cartId) {
            return null;
        }

        return PaymentAttempt::query()
            ->where('provider', self::PROVIDER)
            ->where('cart_id', $cartId)
            ->where('method_code', $payload['value_b'] ?? $payment->getCode())
            ->latest('id')
            ->first();
    }

    public function logEvent(?PaymentAttempt $attempt, string $eventType, array $payload): PaymentGatewayEvent
    {
        $event = $this->eventRepository->create([
            'payment_attempt_id' => $attempt?->id,
            'provider' => self::PROVIDER,
            'event_type' => $eventType,
            'payload' => $this->snapshotPayload($payload),
            'received_at' => now(),
        ]);

        if (! $attempt) {
            return $event;
        }

        $attempt->forceFill([
            'callback_count' => str_contains($eventType, 'redirect') ? $attempt->callback_count + 1 : $attempt->callback_count,
            'ipn_count' => $eventType === 'ipn' ? $attempt->ipn_count + 1 : $attempt->ipn_count,
            'last_callback_at' => str_contains($eventType, 'redirect') ? now() : $attempt->last_callback_at,
            'last_ipn_at' => $eventType === 'ipn' ? now() : $attempt->last_ipn_at,
            'last_payload' => $this->snapshotPayload($payload),
        ])->save();

        return $event;
    }

    public function markCancelledOrFailed(AbstractSslCommerzPayment $payment, array $payload, string $eventType): ?PaymentAttempt
    {
        $attempt = $this->findAttempt($payment, $payload);

        $this->logEvent($attempt, $eventType, $payload);

        if (! $attempt || $attempt->finalized_at) {
            return $attempt;
        }

        $attempt->forceFill([
            'status' => $this->statusMapper->statusFromEvent($eventType),
            'validation_status' => strtoupper($this->statusMapper->statusFromEvent($eventType)),
            'last_payload' => $this->snapshotPayload($payload),
        ])->save();

        return $attempt;
    }

    public function markEventProcessed(PaymentGatewayEvent $event, string $result, ?string $notes = null): void
    {
        $event->forceFill([
            'result' => $result,
            'notes' => $notes,
            'processed_at' => now(),
        ])->save();
    }

    public function markGatewayError(PaymentAttempt $attempt, \Throwable|string $error): void
    {
        $message = $error instanceof \Throwable ? $error->getMessage() : (string) $error;

        $meta = $attempt->meta ?? [];
        $meta['last_error'] = $message;

        $attempt->forceFill([
            'status' => 'error',
            'meta' => $meta,
        ])->save();
    }

    public function markRedirected(PaymentAttempt $attempt, array $sessionResponse): PaymentAttempt
    {
        $meta = $attempt->meta ?? [];
        $meta['session_response'] = $this->snapshotPayload($sessionResponse);

        $attempt->forceFill([
            'status' => 'redirected',
            'session_key' => $sessionResponse['sessionkey'] ?? $sessionResponse['session_key'] ?? null,
            'meta' => $meta,
        ])->save();

        return $attempt->refresh();
    }

    public function resolveAttemptOrFail(AbstractSslCommerzPayment $payment, array $payload): PaymentAttempt
    {
        $attempt = $this->findAttempt($payment, $payload)
            ?? $this->recoverAttempt($payment, $payload);

        if (! $attempt) {
            throw new \RuntimeException('The payment attempt could not be restored for verification.');
        }

        return $attempt;
    }

    public function extractCartId(array $payload): ?int
    {
        foreach ([
            $payload['value_a'] ?? null,
            $payload['cart_id'] ?? null,
        ] as $candidate) {
            if (is_numeric($candidate)) {
                return (int) $candidate;
            }
        }

        $merchantTranId = $this->extractMerchantTransactionId($payload);

        if (
            $merchantTranId
            && preg_match('/^cart_(\d+)_/', $merchantTranId, $matches)
        ) {
            return (int) $matches[1];
        }

        return null;
    }

    public function extractGatewayTransactionId(array $payload): ?string
    {
        foreach ([
            $payload['bank_tran_id'] ?? null,
            $payload['gateway_transaction_id'] ?? null,
            $payload['gateway_tran_id'] ?? null,
            $payload['val_id'] ?? null,
        ] as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                return $candidate;
            }
        }

        return null;
    }

    public function extractMerchantTransactionId(array $payload): ?string
    {
        foreach ([
            $payload['tran_id'] ?? null,
            $payload['merchant_tran_id'] ?? null,
        ] as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                return $candidate;
            }
        }

        return null;
    }

    public function extractSessionKey(array $payload): ?string
    {
        foreach ([
            $payload['sessionkey'] ?? null,
            $payload['session_key'] ?? null,
        ] as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                return $candidate;
            }
        }

        return null;
    }

    protected function snapshotPayload(array $payload): array
    {
        if ((bool) core()->getConfigData('sales.payment_methods.sslcommerz_gateway.log_payloads')) {
            return $payload;
        }

        return Arr::only($payload, [
            'amount',
            'bank_tran_id',
            'card_type',
            'currency',
            'currency_amount',
            'currency_type',
            'sessionkey',
            'status',
            'tran_id',
            'val_id',
            'value_a',
            'value_b',
        ]);
    }

    protected function recoverAttempt(AbstractSslCommerzPayment $payment, array $payload): ?PaymentAttempt
    {
        $cartId = $this->extractCartId($payload);
        $merchantTranId = $this->extractMerchantTransactionId($payload);

        if (! $cartId || ! $merchantTranId) {
            return null;
        }

        $cart = $this->cartRepository->find($cartId);

        if (! $cart) {
            return null;
        }

        return PaymentAttempt::query()->firstOrCreate(
            [
                'provider' => self::PROVIDER,
                'merchant_tran_id' => $merchantTranId,
            ],
            [
                'cart_id' => $cart->id,
                'customer_id' => $cart->customer_id,
                'method_code' => $payload['value_b'] ?? $payment->getCode(),
                'currency' => strtoupper($cart->base_currency_code ?? core()->getBaseCurrencyCode()),
                'amount' => (float) $cart->base_grand_total,
                'status' => 'pending_validation',
                'last_payload' => $this->snapshotPayload($payload),
            ]
        );
    }
}
