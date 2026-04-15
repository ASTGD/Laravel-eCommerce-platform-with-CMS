<?php

namespace Platform\CommerceCore\Services;

use Illuminate\Support\Facades\DB;
use Platform\CommerceCore\Exceptions\BkashVerificationException;
use Platform\CommerceCore\Models\PaymentAttempt;
use Platform\CommerceCore\Models\PaymentGatewayEvent;
use Platform\CommerceCore\Payment\AbstractBkashPayment;
use Platform\CommerceCore\Support\BkashStatusMapper;
use Platform\CommerceCore\Transformers\OrderResource;
use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Repositories\CartRepository;
use Webkul\Sales\Models\OrderTransaction;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Repositories\OrderTransactionRepository;

class BkashFinalizationService
{
    public function __construct(
        protected CartRepository $cartRepository,
        protected InvoiceRepository $invoiceRepository,
        protected OrderRepository $orderRepository,
        protected OrderTransactionRepository $orderTransactionRepository,
        protected BkashAttemptService $attemptService,
        protected BkashStatusMapper $statusMapper,
    ) {}

    public function finalize(AbstractBkashPayment $payment, array $payload, string $source)
    {
        $attempt = $this->attemptService->resolveAttemptOrFail($payment, $payload);
        $event = $this->attemptService->logEvent($attempt, $source, $payload);

        if ($attempt->finalized_at && $attempt->order_id) {
            $this->attemptService->markEventProcessed($event, 'processed');

            return $attempt->order()->firstOrFail();
        }

        try {
            $validated = $this->resolveValidatedPayment($payment, $attempt, $payload);
            $order = $this->finalizeValidatedAttempt($attempt, $validated, $source);

            $this->attemptService->markEventProcessed($event, 'processed');

            return $order;
        } catch (BkashVerificationException $e) {
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
                throw new BkashVerificationException(
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
                    throw new \RuntimeException('The checkout cart could not be restored after bKash payment.');
                }

                if (! $cart->is_active) {
                    $order = $this->findExistingOrder($lockedAttempt, $validated);

                    if (! $order) {
                        throw new \RuntimeException('This cart has already been processed.');
                    }
                } else {
                    Cart::setCart($cart);
                    Cart::collectTotals();

                    $gatewayTransactionId = $this->attemptService->extractGatewayTransactionId($validated);

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

            if ($order->status !== 'processing') {
                $this->orderRepository->update(['status' => 'processing'], $order->id);
                $order->refresh();
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
            $meta['payer_reference'] = $validated['payerReference'] ?? null;
            $meta['customer_msisdn'] = $validated['customerMsisdn'] ?? null;

            $lockedAttempt->forceFill([
                'order_id' => $order->id,
                'gateway_tran_id' => $this->attemptService->extractGatewayTransactionId($validated),
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

    protected function resolveValidatedPayment(AbstractBkashPayment $payment, PaymentAttempt $attempt, array $payload): array
    {
        $paymentId = $attempt->session_key ?: $this->attemptService->extractPaymentId($payload);

        if (! $paymentId) {
            throw new \RuntimeException('bKash did not return a payment identifier.');
        }

        try {
            $validated = $payment->executePayment($paymentId);
        } catch (\Throwable) {
            $validated = $this->queryPayment($payment, $attempt, $paymentId);
        }

        if ($this->statusMapper->isPaid($validated)) {
            return $validated;
        }

        $queried = $this->queryPayment($payment, $attempt, $paymentId);

        if ($this->statusMapper->isPaid($queried)) {
            return $queried;
        }

        throw new BkashVerificationException(
            $this->statusMapper->userMessageForValidated($queried),
            $queried,
            $this->statusMapper->statusFromValidated($queried),
        );
    }

    protected function queryPayment(AbstractBkashPayment $payment, PaymentAttempt $attempt, string $paymentId): array
    {
        $event = $this->attemptService->logEvent($attempt, 'query_payment', [
            'paymentID' => $paymentId,
        ]);

        try {
            $response = $payment->queryPayment($paymentId);

            $this->attemptService->markEventProcessed($event, 'processed');

            return $response;
        } catch (\Throwable $e) {
            $this->attemptService->markEventProcessed($event, 'error', $e->getMessage());

            throw $e;
        }
    }

    protected function assertAttemptMatchesValidation(PaymentAttempt $attempt, array $validated): void
    {
        $paymentId = $this->attemptService->extractPaymentId($validated);

        if ($paymentId && $attempt->session_key && $paymentId !== $attempt->session_key) {
            throw new BkashVerificationException(
                'The bKash response does not match the initiated payment session.',
                $validated,
                'invalid',
            );
        }

        $merchantTranId = $this->attemptService->extractMerchantTransactionId($validated);

        if ($merchantTranId && $merchantTranId !== $attempt->merchant_tran_id) {
            throw new BkashVerificationException(
                'The bKash response does not match the initiated invoice number.',
                $validated,
                'invalid',
            );
        }

        $currency = strtoupper((string) ($validated['currency'] ?? ''));

        if ($currency !== '' && $currency !== strtoupper($attempt->currency)) {
            throw new BkashVerificationException(
                'The bKash payment currency does not match the checkout currency.',
                $validated,
                'invalid',
            );
        }

        if (! $this->isStrictAmountValidation()) {
            return;
        }

        $validatedAmount = $this->extractAmount($validated);

        if ($validatedAmount === null) {
            throw new BkashVerificationException(
                'bKash did not return a verifiable payment amount.',
                $validated,
                'invalid',
            );
        }

        if (abs($validatedAmount - (float) $attempt->amount) > 0.01) {
            throw new BkashVerificationException(
                'The bKash payment amount does not match the checkout total.',
                $validated,
                'invalid',
            );
        }
    }

    protected function extractAmount(array $validated): ?float
    {
        $candidate = $validated['amount'] ?? null;

        return is_numeric($candidate) ? (float) $candidate : null;
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
        return (bool) core()->getConfigData('sales.payment_methods.bkash_gateway.strict_amount_validation');
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
            'status' => strtolower((string) ($validated['transactionStatus'] ?? $validated['statusCode'] ?? 'completed')),
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
