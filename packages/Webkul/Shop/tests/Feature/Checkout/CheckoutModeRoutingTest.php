<?php

use Webkul\Core\Models\CoreConfig;

use function Pest\Laravel\get;

function setCheckoutModeConfig(string $mode): void
{
    CoreConfig::query()->updateOrCreate(
        [
            'code' => 'sales.checkout.shopping_cart.checkout_mode',
            'channel_code' => 'default',
        ],
        [
            'value' => $mode,
        ],
    );
}

it('routes generic checkout entry to the custom one page checkout when onepage mode is selected', function () {
    setCheckoutModeConfig('onepage');

    get(route('shop.checkout.index', ['step' => 'payment']))
        ->assertRedirect(route('shop.checkout.custom.index', ['step' => 'payment']));
});

it('routes generic checkout entry to the native full checkout when full mode is selected', function () {
    setCheckoutModeConfig('full');

    get(route('shop.checkout.index', ['step' => 'payment']))
        ->assertRedirect(route('shop.checkout.onepage.index', ['step' => 'payment']));
});
