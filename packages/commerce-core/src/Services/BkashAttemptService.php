<?php

namespace Platform\CommerceCore\Services;

use Illuminate\Support\Arr;
use Platform\CommerceCore\Models\PaymentAttempt;
use Platform\CommerceCore\Models\PaymentGatewayEvent;
use Platform\CommerceCore\Payment\AbstractBkashPayment;
use Platform\CommerceCore\Repositories\PaymentAttemptRepository;
use Platform\CommerceCore\Repositories\PaymentGatewayEventRepository;
use Platform\CommerceCore\Support\BkashStatusMapper;

class BkashAttemptService
{
    public const PROVIDER = 'bkash';

    public function __construct(
        protected PaymentAttemptRepository $attemptRepository,
        protected PaymentGatewayEventRepository $eventRepository,
        protected BkashStatusMapper $statusMapper,
    ) {}

    public function buildPaymentSnapshot(PaymentAttempt $attempt, array $validated = []): array
    {
        return [
            'provider' => self::PROVIDER,
            'method_code' => $attempt->method_code,
            'attempt_id' => $attempt->id,
            'merchant_invoice_number' => $attempt->merchant_tran_id,
            'payment_id' => $attempt->session_key,
            'gateway_transaction_id' => $attempt->gateway_tran_id,
            'status' => $attempt->status,
            'validation_status' => $attempt->validation_status,
            'finalized_via' => $attempt->finalized_via,
            'validated' => $validated ?: ($attempt->meta['validated'] ?? null),
            'payer_reference' => $validated['payerReference'] ?? ($attempt->meta['payer_reference'] ?? null),
            'customer_msisdn' => $validated['customerMsisdn'] ?? ($attempt->meta['customer_msisdn'] ?? null),
        ];
    }

    public function createInitiatedAttempt(AbstractBkashPayment $payment, $cart, string $merchantTranId): PaymentAttempt
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

    public function findAttempt(AbstractBkashPayment $payment, array $payload): ?PaymentAttempt
    {
        $paymentId = $this->extractPaymentId($payload);

        if ($paymentId) {
            return PaymentAttempt::query()
                ->where('provider', self::PROVIDER)
                ->where('session_key', $paymentId)
                ->first();
        }

        $merchantTranId = $this->extractMerchantTransactionId($payload);

        if ($merchantTranId) {
            return PaymentAttempt::query()
                ->where('provider', self::PROVIDER)
                ->where('merchant_tran_id', $merchantTranId)
                ->first();
        }

        return null;
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
            'callback_count' => str_starts_with($eventType, 'callback_') ? $attempt->callback_count + 1 : $attempt->callback_count,
            'last_callback_at' => str_starts_with($eventType, 'callback_') ? now() : $attempt->last_callback_at,
            'last_payload' => $this->snapshotPayload($payload),
        ])->save();

        return $event;
    }

    public function markCancelledOrFailed(AbstractBkashPayment $payment, array $payload, string $callbackStatus): ?PaymentAttempt
    {
        $attempt = $this->findAttempt($payment, $payload);

        $this->logEvent($attempt, 'callback_'.$callbackStatus, $payload);

        if (! $attempt || $attempt->finalized_at) {
            return $attempt;
        }

        $attempt->forceFill([
            'status' => $this->statusMapper->statusFromCallback($callbackStatus),
            'validation_status' => strtoupper($callbackStatus),
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

    public function markRedirected(PaymentAttempt $attempt, array $createResponse): PaymentAttempt
    {
        $meta = $attempt->meta ?? [];
        $meta['session_response'] = $this->snapshotPayload($createResponse);

        $attempt->forceFill([
            'status' => 'redirected',
            'session_key' => $createResponse['paymentID'] ?? null,
            'meta' => $meta,
        ])->save();

        return $attempt->refresh();
    }

    public function resolveAttemptOrFail(AbstractBkashPayment $payment, array $payload): PaymentAttempt
    {
        $attempt = $this->findAttempt($payment, $payload);

        if (! $attempt) {
            throw new \RuntimeException('The bKash payment attempt could not be restored for verification.');
        }

        return $attempt;
    }

    public function extractPaymentId(array $payload): ?string
    {
        $paymentId = $payload['paymentID'] ?? $payload['paymentId'] ?? null;

        return is_string($paymentId) && $paymentId !== '' ? $paymentId : null;
    }

    public function extractMerchantTransactionId(array $payload): ?string
    {
        foreach ([
            $payload['merchantInvoiceNumber'] ?? null,
            $payload['merchantInvoice'] ?? null,
        ] as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                return $candidate;
            }
        }

        return null;
    }

    public function extractGatewayTransactionId(array $payload): ?string
    {
        $trxId = $payload['trxID'] ?? null;

        return is_string($trxId) && $trxId !== '' ? $trxId : null;
    }

    protected function snapshotPayload(array $payload): array
    {
        if ((bool) core()->getConfigData('sales.payment_methods.bkash_gateway.log_payloads')) {
            return $payload;
        }

        return Arr::only($payload, [
            'amount',
            'currency',
            'customerMsisdn',
            'merchantInvoice',
            'merchantInvoiceNumber',
            'payerReference',
            'paymentID',
            'status',
            'statusCode',
            'statusMessage',
            'transactionStatus',
            'trxID',
        ]);
    }
}
