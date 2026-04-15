<?php

namespace Platform\CommerceCore\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Platform\CommerceCore\Payment\AbstractSslCommerzPayment;
use Platform\CommerceCore\Services\SslCommerzAttemptService;
use Platform\CommerceCore\Services\SslCommerzFinalizationService;
use Platform\CommerceCore\Support\PaymentMethodRegistry;
use Platform\CommerceCore\Support\SslCommerzStatusMapper;
use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Repositories\CartRepository;
use Webkul\Shop\Http\Controllers\Controller;

class SslCommerzController extends Controller
{
    public function __construct(
        protected CartRepository $cartRepository,
        protected SslCommerzAttemptService $attemptService,
        protected SslCommerzFinalizationService $finalizationService,
        protected SslCommerzStatusMapper $statusMapper,
    ) {}

    public function redirect(string $code): RedirectResponse
    {
        $payment = $this->resolvePayment($code);

        if (! $payment->hasValidCredentials()) {
            session()->flash('error', 'Configure SSLCommerz credentials before enabling this payment method.');

            return redirect()->route('shop.checkout.onepage.index', ['step' => 'payment']);
        }

        $cart = Cart::getCart();

        if (! $cart) {
            session()->flash('error', 'Your cart is no longer available.');

            return redirect()->route('shop.checkout.cart.index');
        }

        if ($cart->payment?->method !== $payment->getCode()) {
            session()->flash('error', 'Select the payment method again before continuing.');

            return redirect()->route('shop.checkout.onepage.index', ['step' => 'payment']);
        }

        $attempt = null;

        try {
            Cart::collectTotals();

            $merchantTransactionId = $payment->makeMerchantTransactionId($cart);
            $attempt = $this->attemptService->createInitiatedAttempt($payment, $cart, $merchantTransactionId);
            $session = $payment->createSession($cart, $merchantTransactionId);
            $this->attemptService->markRedirected($attempt, $session);

            $redirectUrl = $payment->resolveRedirectUrl($session);

            if (! $redirectUrl) {
                throw new \RuntimeException('SSLCommerz did not return a checkout URL.');
            }

            return redirect()->away($redirectUrl);
        } catch (\Throwable $e) {
            report($e);

            if ($attempt) {
                $this->attemptService->markGatewayError($attempt, $e);
            }

            return $this->redirectToPaymentStep(
                'Unable to start the online payment right now. Please try again.',
                $cart->id
            );
        }
    }

    public function success(Request $request, string $code): RedirectResponse
    {
        try {
            $order = $this->finalizationService->finalize(
                $this->resolvePayment($code),
                $request->all(),
                'success_redirect',
            );

            session()->flash('order_id', $order->id);
            session()->flash('success', 'Payment completed successfully.');

            return redirect()->route('shop.checkout.onepage.success');
        } catch (\Throwable $e) {
            report($e);

            return $this->redirectToPaymentStep(
                $e->getMessage() ?: 'The payment could not be verified. Please try again.',
                $this->attemptService->extractCartId($request->all())
            );
        }
    }

    public function fail(Request $request, string $code): RedirectResponse
    {
        $payment = $this->resolvePayment($code);
        $attempt = $this->attemptService->markCancelledOrFailed($payment, $request->all(), 'fail_redirect');

        return $this->redirectToPaymentStep(
            $this->statusMapper->userMessageForStatus('failed'),
            $attempt?->cart_id ?? $this->attemptService->extractCartId($request->all())
        );
    }

    public function cancel(Request $request, string $code): RedirectResponse
    {
        $payment = $this->resolvePayment($code);
        $attempt = $this->attemptService->markCancelledOrFailed($payment, $request->all(), 'cancel_redirect');

        return $this->redirectToPaymentStep(
            $this->statusMapper->userMessageForStatus('cancelled'),
            $attempt?->cart_id ?? $this->attemptService->extractCartId($request->all())
        );
    }

    public function ipn(Request $request, string $code): JsonResponse
    {
        try {
            $order = $this->finalizationService->finalize(
                $this->resolvePayment($code),
                $request->all(),
                'ipn',
            );

            return response()->json([
                'status' => 'ok',
                'order_id' => $order->id,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    protected function redirectToPaymentStep(string $message, ?int $cartId = null): RedirectResponse
    {
        if ($cartId && $cart = $this->cartRepository->find($cartId)) {
            Cart::setCart($cart);
        }

        session()->flash('error', $message);

        return redirect()->route('shop.checkout.onepage.index', ['step' => 'payment']);
    }

    protected function resolvePayment(string $code): AbstractSslCommerzPayment
    {
        $paymentConfig = config('payment_methods.'.PaymentMethodRegistry::canonicalCode($code));

        abort_unless($paymentConfig && isset($paymentConfig['class']), 404);

        $payment = app($paymentConfig['class']);

        abort_unless($payment instanceof AbstractSslCommerzPayment, 404);

        return $payment;
    }
}
