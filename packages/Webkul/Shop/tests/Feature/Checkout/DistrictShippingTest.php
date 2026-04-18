<?php

use Webkul\Checkout\Facades\Cart as CartFacade;
use Webkul\Checkout\Models\CartAddress;
use Webkul\Core\Models\CoreConfig;
use Webkul\Payment\Tests\Concerns\ProvidePaymentHelpers;
use Webkul\Shipping\Facades\Shipping;

use function Pest\Laravel\postJson;

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

beforeEach(function () {
    setCourierConfig('sales.carriers.courier.active', 1);
    setCourierConfig('sales.carriers.courier.title', 'Courier');
    setCourierConfig('sales.carriers.courier.description', 'District-based delivery charges');
    setCourierConfig('sales.carriers.courier.default_rate', 120);
    setCourierConfig('sales.carriers.courier.district_rates', "Dhaka=60\nRajshahi=140");
    setCourierConfig('sales.carriers.courier.dhaka_district', 'Dhaka');
    setCourierConfig('sales.carriers.courier.dhaka_title', 'Dhaka Delivery');
    setCourierConfig('sales.carriers.courier.dhaka_rate', 60);
    setCourierConfig('sales.carriers.courier.outside_dhaka_title', 'Outside Dhaka Delivery');
    setCourierConfig('sales.carriers.courier.outside_dhaka_rate', 120);
    setCourierConfig('sales.payment_methods.cashondelivery.active', 1);
});

it('returns the Dhaka district rate from the courier carrier', function () {
    $cart = $this->createCartWithItems('cashondelivery');

    CartAddress::query()
        ->where('cart_id', $cart->id)
        ->where('address_type', CartAddress::ADDRESS_TYPE_BILLING)
        ->update(['state' => 'Dhaka']);

    CartAddress::query()
        ->where('cart_id', $cart->id)
        ->where('address_type', CartAddress::ADDRESS_TYPE_SHIPPING)
        ->update(['state' => 'Dhaka']);

    CartFacade::refreshCart();

    $rates = Shipping::collectRates();

    expect($rates['shippingMethods'])->toHaveKey('courier');

    $courierRates = collect($rates['shippingMethods']['courier']['rates']);

    expect($courierRates->pluck('method')->all())
        ->toContain('courier_dhaka');

    expect($courierRates->pluck('method')->all())
        ->not->toContain('courier_outside_dhaka');

    expect($courierRates->pluck('base_price')->all())
        ->toContain(60.0);
});

it('returns the outside Dhaka rate from the courier carrier', function () {
    $cart = $this->createCartWithItems('cashondelivery');

    CartAddress::query()
        ->where('cart_id', $cart->id)
        ->where('address_type', CartAddress::ADDRESS_TYPE_BILLING)
        ->update(['state' => 'Rajshahi']);

    CartAddress::query()
        ->where('cart_id', $cart->id)
        ->where('address_type', CartAddress::ADDRESS_TYPE_SHIPPING)
        ->update(['state' => 'Rajshahi']);

    CartFacade::refreshCart();

    $rates = Shipping::collectRates();

    expect($rates['shippingMethods'])->toHaveKey('courier');

    $courierRates = collect($rates['shippingMethods']['courier']['rates']);

    expect($courierRates->pluck('method')->all())
        ->toContain('courier_rajshahi');

    expect($courierRates->pluck('method')->all())
        ->not->toContain('courier_dhaka');

    expect($courierRates->pluck('base_price')->all())
        ->toContain(140.0);
});

it('auto-selects the courier district rate and returns payment methods after address save', function () {
    $cart = $this->createCartWithItems('cashondelivery');
    $customer = $cart->customer;

    $this->loginAsCustomer($customer);

    postJson(route('shop.checkout.custom.addresses.store'), [
        'billing' => [
            'use_for_shipping' => true,
            'first_name' => $customer->first_name,
            'last_name' => $customer->last_name,
            'email' => $customer->email,
            'address' => ['House 12'],
            'country' => 'BD',
            'state' => 'Dhaka',
            'city' => 'Dhaka',
            'postcode' => '1212',
            'phone' => '+8801711111111',
        ],
    ])
        ->assertOk()
        ->assertJsonPath('data.shippingMethods.courier.rates.0.method', 'courier_dhaka')
        ->assertJsonPath('data.shippingMethods.courier.rates.0.base_price', 60)
        ->assertJsonStructure([
            'data' => [
                'payment_methods' => [
                    [
                        'method',
                        'method_title',
                        'description',
                        'sort',
                    ],
                ],
            ],
        ]);

    $cart->refresh();

    expect($cart->shipping_method)->toBe('courier_dhaka')
        ->and((float) $cart->shipping_amount)->toBe(60.0)
        ->and((float) $cart->base_shipping_amount)->toBe(60.0);
});

it('auto-selects the district rate for custom checkout even when the courier carrier is inactive', function () {
    setCourierConfig('sales.carriers.courier.active', 0);

    $cart = $this->createCartWithItems('cashondelivery');
    $customer = $cart->customer;

    $this->loginAsCustomer($customer);

    postJson(route('shop.checkout.custom.addresses.store'), [
        'billing' => [
            'use_for_shipping' => true,
            'first_name' => $customer->first_name,
            'last_name' => $customer->last_name,
            'email' => $customer->email,
            'address' => ['House 12'],
            'country' => 'BD',
            'state' => 'Dhaka',
            'city' => 'Dhaka',
            'postcode' => '1212',
            'phone' => '+8801711111111',
        ],
    ])
        ->assertOk()
        ->assertJsonPath('data.shippingMethods.courier.rates.0.method', 'courier_dhaka')
        ->assertJsonPath('data.shippingMethods.courier.rates.0.base_price', 60)
        ->assertJsonPath('data.payment_methods.0.method', 'cashondelivery');

    $cart->refresh();

    expect($cart->shipping_method)->toBe('courier_dhaka')
        ->and((float) $cart->shipping_amount)->toBe(60.0)
        ->and((float) $cart->base_shipping_amount)->toBe(60.0)
        ->and($cart->selected_shipping_rate)->not->toBeNull()
        ->and($cart->selected_shipping_rate->method)->toBe('courier_dhaka');
});

it('uses the configured district rate map for non-dhaka districts', function () {
    $cart = $this->createCartWithItems('cashondelivery');
    $customer = $cart->customer;

    $this->loginAsCustomer($customer);

    postJson(route('shop.checkout.custom.addresses.store'), [
        'billing' => [
            'use_for_shipping' => true,
            'first_name' => $customer->first_name,
            'last_name' => $customer->last_name,
            'email' => $customer->email,
            'address' => ['House 12'],
            'country' => 'BD',
            'state' => 'Rajshahi',
            'city' => 'Rajshahi',
            'postcode' => '6000',
            'phone' => '+8801711111111',
        ],
    ])
        ->assertOk()
        ->assertJsonPath('data.shippingMethods.courier.rates.0.method', 'courier_rajshahi')
        ->assertJsonPath('data.shippingMethods.courier.rates.0.base_price', 140);

    $cart->refresh();

    expect($cart->shipping_method)->toBe('courier_rajshahi')
        ->and((float) $cart->shipping_amount)->toBe(140.0);
});
