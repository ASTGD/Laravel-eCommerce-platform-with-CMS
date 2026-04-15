<?php

namespace Platform\CommerceCore\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Platform\CommerceCore\Services\BkashTokenService;
use Platform\CommerceCore\Support\PaymentChannel;
use Webkul\Checkout\Facades\Cart;
use Webkul\Payment\Payment\Payment;

abstract class AbstractBkashPayment extends Payment
{
    public function __construct(
        protected BkashTokenService $tokenService,
    ) {}

    public function getRedirectUrl()
    {
        return route('commerce-core.bkash.redirect', ['code' => $this->getCode()]);
    }

    public function isAvailable()
    {
        return parent::isAvailable()
            && PaymentChannel::mode() === PaymentChannel::CUSTOM
            && $this->hasValidCredentials();
    }

    public function getTitle()
    {
        return $this->getConfigData('title') ?: config('payment_methods.'.$this->getCode().'.title');
    }

    public function getDescription()
    {
        return $this->getConfigData('description') ?: config('payment_methods.'.$this->getCode().'.description');
    }

    public function getImage()
    {
        $url = $this->getConfigData('image');

        return $url ? Storage::url($url) : bagisto_asset('images/money-transfer.png', 'shop');
    }

    public function getSortOrder()
    {
        return (int) ($this->getConfigData('sort') ?: config('payment_methods.'.$this->getCode().'.sort', 99));
    }

    public function hasValidCredentials(): bool
    {
        return (bool) (
            $this->getBaseUrl()
            && $this->getUsername()
            && $this->getPassword()
            && $this->getAppKey()
            && $this->getAppSecret()
        );
    }

    public function isSandbox(): bool
    {
        return (bool) core()->getConfigData('sales.payment_methods.bkash_gateway.sandbox');
    }

    public function getBaseUrl(): ?string
    {
        $key = $this->isSandbox()
            ? 'sales.payment_methods.bkash_gateway.sandbox_base_url'
            : 'sales.payment_methods.bkash_gateway.base_url';

        $url = core()->getConfigData($key);

        return is_string($url) ? rtrim($url, '/') : null;
    }

    public function getUsername(): ?string
    {
        return core()->getConfigData('sales.payment_methods.bkash_gateway.username');
    }

    public function getPassword(): ?string
    {
        return core()->getConfigData('sales.payment_methods.bkash_gateway.password');
    }

    public function getAppKey(): ?string
    {
        return core()->getConfigData('sales.payment_methods.bkash_gateway.app_key');
    }

    public function getAppSecret(): ?string
    {
        return core()->getConfigData('sales.payment_methods.bkash_gateway.app_secret');
    }

    public function getRequestTimeout(): int
    {
        return (int) (core()->getConfigData('sales.payment_methods.bkash_gateway.request_timeout') ?: 30);
    }

    public function makeMerchantTransactionId($cart): string
    {
        return sprintf('bkash_cart_%d_%s', $cart->id, strtoupper(Str::random(12)));
    }

    public function createPayment($cart = null, ?string $merchantTransactionId = null): array
    {
        $cart ??= Cart::getCart();
        $merchantTransactionId ??= $this->makeMerchantTransactionId($cart);

        $response = $this->requestJson(
            '/tokenized/checkout/create',
            [
                'mode' => '0011',
                'payerReference' => $this->resolvePayerReference($cart),
                'callbackURL' => route('commerce-core.bkash.callback', ['code' => $this->getCode()]),
                'amount' => number_format((float) $cart->base_grand_total, 2, '.', ''),
                'currency' => strtoupper($cart->base_currency_code ?? core()->getBaseCurrencyCode()),
                'intent' => 'sale',
                'merchantInvoiceNumber' => $merchantTransactionId,
            ]
        );

        if (($response['statusCode'] ?? null) !== '0000') {
            throw new \RuntimeException($response['statusMessage'] ?? 'Unable to initiate bKash payment.');
        }

        if (empty($response['paymentID']) || empty($response['bkashURL'])) {
            throw new \RuntimeException('bKash did not return a payment session or redirect URL.');
        }

        return $response;
    }

    public function executePayment(string $paymentId): array
    {
        return $this->requestJson('/tokenized/checkout/execute', [
            'paymentID' => $paymentId,
        ]);
    }

    public function queryPayment(string $paymentId): array
    {
        return $this->requestJson('/tokenized/checkout/payment/status', [
            'paymentID' => $paymentId,
        ]);
    }

    public function refundTransaction(string $paymentId, string $trxId, float $amount, string $sku, string $reason): array
    {
        return $this->requestJson('/tokenized/checkout/payment/refund', [
            'amount' => number_format($amount, 2, '.', ''),
            'paymentID' => $paymentId,
            'trxID' => $trxId,
            'sku' => $sku,
            'reason' => $reason,
        ]);
    }

    public function queryRefundStatus(string $paymentId, string $trxId): array
    {
        return $this->requestJson('/tokenized/checkout/payment/refund', [
            'paymentID' => $paymentId,
            'trxID' => $trxId,
        ]);
    }

    public function forgetCachedToken(): void
    {
        $this->tokenService->forgetTokens($this);
    }

    protected function requestJson(string $path, array $payload): array
    {
        $response = Http::asJson()
            ->acceptJson()
            ->timeout($this->getRequestTimeout())
            ->withHeaders([
                'Authorization' => $this->tokenService->resolveIdToken($this),
                'X-App-Key' => $this->getAppKey(),
            ])
            ->post($this->getBaseUrl().$path, $payload)
            ->throw()
            ->json();

        if (! is_array($response)) {
            throw new \RuntimeException('bKash returned an unreadable response.');
        }

        return $response;
    }

    protected function resolvePayerReference($cart): string
    {
        $billingAddress = $cart->billing_address;

        $candidate = $billingAddress?->phone
            ?: $cart->customer_email
            ?: 'cart-'.$cart->id;

        $candidate = str_replace(['<', '>', '&'], '', (string) $candidate);

        return Str::limit($candidate, 255, '');
    }
}
