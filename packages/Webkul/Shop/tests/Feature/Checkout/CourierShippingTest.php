<?php

use Webkul\Core\Models\CoreConfig;
use Webkul\Payment\Tests\Concerns\ProvidePaymentHelpers;
use Webkul\Shipping\Facades\Shipping;

uses(ProvidePaymentHelpers::class);

function setCourierConfig(string $code, mixed $value): void
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

it('collects home delivery and courier pick-up rates from the custom courier carrier', function () {
    $this->createCartWithItems('cashondelivery');

    setCourierConfig('sales.carriers.courier.active', 1);
    setCourierConfig('sales.carriers.courier.title', 'Courier');
    setCourierConfig('sales.carriers.courier.description', 'Bangladesh courier rates');
    setCourierConfig('sales.carriers.courier.home_delivery_active', 1);
    setCourierConfig('sales.carriers.courier.home_delivery_title', 'Home Delivery');
    setCourierConfig('sales.carriers.courier.home_delivery_rate', 120);
    setCourierConfig('sales.carriers.courier.pickup_active', 1);
    setCourierConfig('sales.carriers.courier.pickup_title', 'Courier Pick-up');
    setCourierConfig('sales.carriers.courier.pickup_rate', 60);

    $rates = Shipping::collectRates();

    expect($rates['shippingMethods'])->toHaveKey('courier');

    $courierRates = collect($rates['shippingMethods']['courier']['rates']);

    expect($courierRates->pluck('method')->all())
        ->toContain('courier_home_delivery')
        ->toContain('courier_pickup');

    expect($courierRates->pluck('base_price')->all())
        ->toContain(120.0)
        ->toContain(60.0);
});
