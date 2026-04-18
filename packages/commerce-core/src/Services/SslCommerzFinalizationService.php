<?php

namespace Platform\CommerceCore\Services;

use Illuminate\Support\Facades\DB;
use Platform\CommerceCore\Exceptions\SslCommerzVerificationException;
use Platform\CommerceCore\Models\PaymentAttempt;
use Platform\CommerceCore\Payment\AbstractSslCommerzPayment;
use Platform\CommerceCore\Support\SslCommerzStatusMapper;
use Platform\CommerceCore\Transformers\OrderResource;
use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Repositories\CartRepository;
use Webkul\Sales\Models\OrderTransaction;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Repositories\OrderTransactionRepository;

class SslCommerzFinalizationService
{
    public function __construct(
        protected CartRepository $cartRepository,
        protected InvoiceRepository $invoiceRepository,
        protected OrderRepository $orderRepository,
        protected OrderTransactionRepository $orderTransactionRepository,
        protected SslCommerzAttemptService $attemptService,
        protected SslCommerzStatusMapper $statusMapper,
    ) {}

    public function finalize(AbstractSslCommerzPayment $payment, array $payload, string $source)
    {
        $attempt = $this->attemptService->resolveAttemptOrFail($payment, $payload);
        $event = $this->attemptService->logEvent($attempt, $source, $payload);

        try {
            $validated = $payment->validateTransaction($payload);
        } catch (\Throwable $e) {
            $this->markAttemptAsInvalid($attempt, ['validation_error' => $e->getMessage()]);
            $this->attemptService->markEventProcessed($event, 'error', $e->getMessage());

            throw $e;
        }

        try {
            $order = $this->finalizeValidatedAttempt($attempt, $validated, $source);

            $this->attemptService->markEventProcessed($event, 'processed');

            return $order;
        } catch (SslCommerzVerificationException $e) {
            $this->markAttemptAsInvalid($attempt->fresh(), $e->validated(), $e->attemptStatus());
            $this->attemptService->markEventProcessed($event, 'rejected', $e->getMessage());

            throw $e;
        } catch (\Throwable $e) {
            $this->attemptService->markEventProcessed($event, 'error', $e->getMessage());

            throw $e;
        }
    }

    public function finalizeValidatedAttempt(PaymentAttempt $attempt, array $validated, string $source)
    {
        return DB::transaction(function () use ($attempt, $validated, $source) {
            /** @var PaymentAttempt $lockedAttempt */
            $lockedAttempt = PaymentAttempt::query()
                ->whereKey($attempt->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedAttempt->finalized_at && $lockedAttempt->order_id) {
                return $lockedAttempt->order()->firstOrFail();
            }

            $this->assertAttemptMatchesValidation($lockedAttempt, $validated);

            $status = $this->statusMapper->statusFromValidated($validated);

            if ($status !== 'paid') {
                throw new SslCommerzVerificationException(
                    $this->statusMapper->userMessageForValidated($validated),
                    $validated,
                    $status,
                );
            }

            $order = $this->findExistingOrder($lockedAttempt, $validated);

            if (! $order) {
                $cart = $lockedAttempt->cart_id
                    ? $this->cartRepository->find($lockedAttempt->cart_id)
                    : null;

                if (! $cart) {
                    throw new \RuntimeException('The checkout cart could not be restored after payment.');
                }

                if (! $cart->is_active) {
                    $order = $this->findExistingOrder($lockedAttempt, $validated);

                    if (! $order) {
                        throw new \RuntimeException('This cart has already been processed.');
                    }
                } else {
                    Cart::setCart($cart);
                    Cart::collectTotals();

                    $gatewayTransactionId = $this->attemptService->extractGatewayTransactionId($validated)
                        ?? $this->attemptService->extractGatewayTransactionId($lockedAttempt->last_payload ?? []);

                    $data = (new OrderResource($cart))->jsonSerialize();
                    $data['payment']['additional'] = array_merge(
                        $this->attemptService->buildPaymentSnapshot($lockedAttempt, $validated),
                        [
                            'gateway_transaction_id' => $gatewayTransactionId,
                            'status' => 'paid',
                            'validation_status' => $this->statusMapper->validationStatus($validated),
                            'finalized_via' => $source,
                        ]
                    );

                    $order = $this->orderRepository->create($data);
                }
            }

            $invoice = $order->invoices()->first();

            if (! $invoice && $order->canInvoice()) {
                $invoice = $this->invoiceRepository->create($this->prepareInvoiceData($order));
            }

            $this->upsertOrderTransaction($order, $invoice?->id, $lockedAttempt, $validated);

            if ($lockedAttempt->cart_id && $cart = $this->cartRepository->find($lockedAttempt->cart_id)) {
                Cart::setCart($cart);

                if ($cart->is_active) {
                    Cart::deActivateCart();
                }
            }

            $meta = $lockedAttempt->meta ?? [];
            $meta['validated'] = $validated;

            $gatewayTransactionId = $this->attemptService->extractGatewayTransactionId($validated)
                ?? $this->attemptService->extractGatewayTransactionId($lockedAttempt->last_payload ?? []);

            $lockedAttempt->forceFill([
                'order_id' => $order->id,
                'gateway_tran_id' => $gatewayTransactionId,
                'status' => 'paid',
                'validation_status' => $this->statusMapper->validationStatus($validated),
                'finalized_via' => $source,
                'finalized_at' => now(),
                'meta' => $meta,
                'last_payload' => $validated,
            ])->save();

            return $order->fresh();
        }, 3);
    }

    protected function assertAttemptMatchesValidation(PaymentAttempt $attempt, array $validated): void
    {
        $merchantTranId = $this->attemptService->extractMerchantTransactionId($validated);

        if ($merchantTranId && $merchantTranId !== $attempt->merchant_tran_id) {
            throw new SslCommerzVerificationException(
                'The payment response does not match the initiated transaction.',
                $validated,
                'invalid',
            );
        }

        $methodCode = $validated['value_b'] ?? $validated['method_code'] ?? null;

        if (is_string($methodCode) && $methodCode !== '' && $methodCode !== $attempt->method_code) {
            throw new SslCommerzVerificationException(
                'The payment method does not match the initiated transaction.',
                $validated,
                'invalid',
            );
        }

        $currency = strtoupper((string) ($validated['currency_type'] ?? $validated['currency'] ?? ''));

        if ($currency !== '' && $currency !== strtoupper($attempt->currency)) {
            throw new SslCommerzVerificationException(
                'The payment currency does not match the checkout currency.',
                $validated,
                'invalid',
            );
        }

        if (! $this->isStrictAmountValidation()) {
            return;
        }

        $validatedAmount = $this->extractAmount($validated);

        if ($validatedAmount === null) {
            throw new SslCommerzVerificationException(
                'SSLCommerz did not return a verifiable payment amount.',
                $validated,
                'invalid',
            );
        }

        if (abs($validatedAmount - (float) $attempt->amount) > 0.01) {
            throw new SslCommerzVerificationException(
                'The payment amount does not match the checkout total.',
                $validated,
                'invalid',
            );
        }
    }

    protected function extractAmount(array $validated): ?float
    {
        foreach ([
            $validated['amount'] ?? null,
            $validated['currency_amount'] ?? null,
            $validated['store_amount'] ?? null,
        ] as $candidate) {
            if (is_numeric($candidate)) {
                return (float) $candidate;
            }
        }

        return null;
    }

    protected function findExistingOrder(PaymentAttempt $attempt, array $validated)
    {
        if ($attempt->order_id && $order = $attempt->order()->first()) {
            return $order;
        }

        if ($attempt->cart_id && $order = $this->orderRepository->findOneWhere(['cart_id' => $attempt->cart_id])) {
            return $order;
        }

        $gatewayTransactionId = $this->attemptService->extractGatewayTransactionId($validated);

        if (
            $gatewayTransactionId
            && $transaction = OrderTransaction::query()
                ->where('transaction_id', $gatewayTransactionId)
                ->first()
        ) {
            return $transaction->order;
        }

        return null;
    }

    protected function isStrictAmountValidation(): bool
    {
        return (bool) core()->getConfigData('sales.payment_methods.sslcommerz_gateway.strict_amount_validation');
    }

    protected function markAttemptAsInvalid(PaymentAttempt $attempt, array $validated, ?string $status = null): void
    {
        $meta = $attempt->meta ?? [];
        $meta['validated'] = $validated;

        $attempt->forceFill([
            'gateway_tran_id' => $this->attemptService->extractGatewayTransactionId($validated),
            'status' => $status ?? $this->statusMapper->statusFromValidated($validated),
            'validation_status' => $this->statusMapper->validationStatus($validated) ?? strtoupper((string) ($status ?? 'INVALID')),
            'meta' => $meta,
            'last_payload' => $validated,
        ])->save();
    }

    protected function prepareInvoiceData($order): array
    {
        $items = [];

        foreach ($order->items as $item) {
            if ($item->qty_to_invoice > 0) {
                $items[$item->id] = $item->qty_to_invoice;
            }
        }

        return [
            'order_id' => $order->id,
            'invoice' => [
                'items' => $items,
            ],
        ];
    }

    protected function upsertOrderTransaction($order, ?int $invoiceId, PaymentAttempt $attempt, array $validated): void
    {
        $transactionId = $this->attemptService->extractGatewayTransactionId($validated)
            ?? $attempt->merchant_tran_id;

        $transaction = OrderTransaction::query()
            ->where('transaction_id', $transactionId)
            ->first();

        $payload = [
            'status' => strtolower((string) ($validated['status'] ?? 'VALID')),
            'type' => $order->payment->method,
            'payment_method' => $order->payment->method,
            'order_id' => $order->id,
            'invoice_id' => $invoiceId ?? $transaction?->invoice_id ?? 0,
            'amount' => $order->base_grand_total,
            'data' => json_encode([
                'attempt_id' => $attempt->id,
                'validated' => $validated,
            ]),
        ];

        if ($transaction) {
            $transaction->fill($payload)->save();

            return;
        }

        $this->orderTransactionRepository->create([
            'transaction_id' => $transactionId,
            ...$payload,
        ]);
    }
}
