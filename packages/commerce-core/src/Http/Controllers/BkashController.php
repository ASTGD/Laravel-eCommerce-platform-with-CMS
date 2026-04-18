<?php

namespace Platform\CommerceCore\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Platform\CommerceCore\Payment\AbstractBkashPayment;
use Platform\CommerceCore\Services\BkashAttemptService;
use Platform\CommerceCore\Services\BkashFinalizationService;
use Platform\CommerceCore\Support\BkashStatusMapper;
use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Repositories\CartRepository;
use Webkul\Shop\Http\Controllers\Controller;

class BkashController extends Controller
{
    public function __construct(
        protected CartRepository $cartRepository,
        protected BkashAttemptService $attemptService,
        protected BkashFinalizationService $finalizationService,
        protected BkashStatusMapper $statusMapper,
    ) {}

    public function redirect(string $code): RedirectResponse
    {
        $payment = $this->resolvePayment($code);

        if (! $payment->hasValidCredentials()) {
            session()->flash('error', 'Configure the direct bKash credentials before enabling this payment method.');

            return redirect()->route('shop.checkout.index', ['step' => 'payment']);
        }

        $cart = Cart::getCart();

        if (! $cart) {
            session()->flash('error', 'Your cart is no longer available.');

            return redirect()->route('shop.checkout.cart.index');
        }

        if ($cart->payment?->method !== $payment->getCode()) {
            session()->flash('error', 'Select the payment method again before continuing.');

            return redirect()->route('shop.checkout.index', ['step' => 'payment']);
        }

        $attempt = null;

        try {
            Cart::collectTotals();

            $merchantTransactionId = $payment->makeMerchantTransactionId($cart);
            $attempt = $this->attemptService->createInitiatedAttempt($payment, $cart, $merchantTransactionId);
            $response = $payment->createPayment($cart, $merchantTransactionId);
            $this->attemptService->markRedirected($attempt, $response);

            return redirect()->away($response['bkashURL']);
        } catch (\Throwable $e) {
            report($e);

            if ($attempt) {
                $this->attemptService->markGatewayError($attempt, $e);
            }

            return $this->redirectToPaymentStep(
                'Unable to start the bKash payment right now. Please try again.',
                $cart->id
            );
        }
    }

    public function callback(Request $request, string $code): RedirectResponse
    {
        $status = strtolower((string) $request->input('status'));

        return match ($status) {
            'success' => $this->handleSuccess($request, $code),
            'failure', 'failed' => $this->handleFailure($request, $code),
            'cancel', 'cancelled', 'canceled' => $this->handleCancel($request, $code),
            default => $this->redirectToPaymentStep(
                'bKash returned an unknown payment status. Please try again.',
                $this->attemptService->findAttempt($this->resolvePayment($code), $request->all())?->cart_id
            ),
        };
    }

    protected function handleSuccess(Request $request, string $code): RedirectResponse
    {
        try {
            $order = $this->finalizationService->finalize(
                $this->resolvePayment($code),
                $request->all(),
                'callback_success',
            );

            session()->put('order_id', $order->id);
            session()->flash('order_id', $order->id);
            session()->flash('success', 'Payment completed successfully.');

            return redirect()->route('shop.checkout.success', ['order' => $order->id]);
        } catch (\Throwable $e) {
            report($e);

            $attempt = $this->attemptService->findAttempt($this->resolvePayment($code), $request->all());

            return $this->redirectToPaymentStep(
                $e->getMessage() ?: 'The bKash payment could not be verified. Please try again.',
                $attempt?->cart_id
            );
        }
    }

    protected function handleFailure(Request $request, string $code): RedirectResponse
    {
        $payment = $this->resolvePayment($code);
        $attempt = $this->attemptService->markCancelledOrFailed($payment, $request->all(), 'failure');

        return $this->redirectToPaymentStep(
            $this->statusMapper->userMessageForStatus('failed'),
            $attempt?->cart_id
        );
    }

    protected function handleCancel(Request $request, string $code): RedirectResponse
    {
        $payment = $this->resolvePayment($code);
        $attempt = $this->attemptService->markCancelledOrFailed($payment, $request->all(), 'cancel');

        return $this->redirectToPaymentStep(
            $this->statusMapper->userMessageForStatus('cancelled'),
            $attempt?->cart_id
        );
    }

    protected function redirectToPaymentStep(string $message, ?int $cartId = null): RedirectResponse
    {
        if ($cartId && $cart = $this->cartRepository->find($cartId)) {
            Cart::setCart($cart);
        }

        session()->flash('error', $message);

        return redirect()->route('shop.checkout.index', ['step' => 'payment']);
    }

    protected function resolvePayment(string $code): AbstractBkashPayment
    {
        $paymentConfig = config('payment_methods.'.$code);

        abort_unless($paymentConfig && isset($paymentConfig['class']), 404);

        $payment = app($paymentConfig['class']);

        abort_unless($payment instanceof AbstractBkashPayment, 404);

        return $payment;
    }
}
