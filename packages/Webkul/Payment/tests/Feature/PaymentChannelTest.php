<?php

use Webkul\Core\Models\CoreConfig;
use Webkul\Payment\Facades\Payment as PaymentFacade;
use Webkul\Payment\Tests\Concerns\ProvidePaymentHelpers;

uses(ProvidePaymentHelpers::class);

function setPaymentConfig(string $code, mixed $value): void
{
    CoreConfig::query()->updateOrCreate(
        [
            'code' => $code,
            'channel_code' => 'default',
        ],
        [
            'value' => (string) $value,
        ],
    );
}

beforeEach(function () {
    $this->createCartWithItems('cashondelivery');

    setPaymentConfig('sales.payment_methods.cashondelivery.active', 1);
    setPaymentConfig('sales.payment_methods.moneytransfer.active', 1);
    setPaymentConfig('sales.payment_methods.mode.channel', 'default');
    setPaymentConfig('sales.payment_methods.bkash_gateway.sandbox', 1);
    setPaymentConfig('sales.payment_methods.bkash_gateway.sandbox_base_url', 'https://tokenized.sandbox.bka.sh/v1.2.0-beta');
    setPaymentConfig('sales.payment_methods.bkash_gateway.username', 'sandbox-user');
    setPaymentConfig('sales.payment_methods.bkash_gateway.password', 'sandbox-password');
    setPaymentConfig('sales.payment_methods.bkash_gateway.app_key', 'sandbox-app-key');
    setPaymentConfig('sales.payment_methods.bkash_gateway.app_secret', 'sandbox-app-secret');
    setPaymentConfig('sales.payment_methods.bkash.active', 1);
    setPaymentConfig('sales.payment_methods.sslcommerz_gateway.sandbox', 1);
    setPaymentConfig('sales.payment_methods.sslcommerz_gateway.store_id', 'test_store');
    setPaymentConfig('sales.payment_methods.sslcommerz_gateway.store_password', 'test_password');
    setPaymentConfig('sales.payment_methods.sslcommerz_card.active', 1);
    setPaymentConfig('sales.payment_methods.sslcommerz_bkash.active', 1);
    setPaymentConfig('sales.payment_methods.sslcommerz_nagad.active', 1);
});

it('keeps native payment methods in default mode', function () {
    $methods = collect(PaymentFacade::getPaymentMethods())->pluck('method');

    expect($methods->all())
        ->toContain('cashondelivery')
        ->toContain('moneytransfer')
        ->not->toContain('sslcommerz_card')
        ->not->toContain('bkash')
        ->not->toContain('sslcommerz_bkash')
        ->not->toContain('sslcommerz_nagad');
});

it('switches storefront payment methods to the custom Bangladesh set when custom mode is enabled', function () {
    setPaymentConfig('sales.payment_methods.mode.channel', 'custom');

    $methods = collect(PaymentFacade::getPaymentMethods())->pluck('method');

    expect($methods->all())
        ->toContain('cashondelivery')
        ->toContain('sslcommerz_card')
        ->toContain('bkash')
        ->not->toContain('sslcommerz_bkash')
        ->not->toContain('sslcommerz_nagad')
        ->not->toContain('moneytransfer');
});
