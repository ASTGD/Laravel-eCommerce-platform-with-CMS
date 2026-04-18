<?php

use Webkul\Core\Models\CoreConfig;
use Webkul\Payment\Tests\Concerns\ProvidePaymentHelpers;

use function Pest\Laravel\getJson;

uses(ProvidePaymentHelpers::class);

function setCheckoutStateConfig(string $code, mixed $value): void
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
    setCheckoutStateConfig('sales.checkout.shopping_cart.allow_guest_checkout', 1);
    setCheckoutStateConfig('sales.carriers.courier.active', 1);
    setCheckoutStateConfig('sales.carriers.courier.title', 'Courier');
    setCheckoutStateConfig('sales.carriers.courier.description', 'District-based delivery charges');
    setCheckoutStateConfig('sales.carriers.courier.dhaka_district', 'Dhaka');
    setCheckoutStateConfig('sales.carriers.courier.dhaka_title', 'Dhaka Delivery');
    setCheckoutStateConfig('sales.carriers.courier.dhaka_rate', 60);
    setCheckoutStateConfig('sales.carriers.courier.outside_dhaka_title', 'Outside Dhaka Delivery');
    setCheckoutStateConfig('sales.carriers.courier.outside_dhaka_rate', 120);
    setCheckoutStateConfig('sales.payment_methods.cashondelivery.active', 1);
});

it('returns a single checkout state contract for the storefront checkout screen', function () {
    $cart = $this->createCartWithItems('cashondelivery');
    $customer = $cart->customer;

    $this->loginAsCustomer($customer);

    $response = getJson(route('shop.checkout.custom.state'))
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'single_flow',
                'cart' => [
                    'id',
                    'shipping_method',
                    'shipping_method_title',
                    'shipping_method_description',
                    'payment_method',
                    'payment_method_title',
                ],
                'checkout' => [
                    'allow_guest_checkout',
                    'single_screen',
                ],
                'customer' => [
                    'is_authenticated',
                    'draft' => [
                        'name',
                        'full_name',
                        'first_name',
                        'last_name',
                        'email',
                        'phone',
                        'country',
                    ],
                ],
                'form' => [
                    'mode',
                    'single_address' => [
                        'visible_fields' => [
                            [
                                'name',
                                'label',
                                'type',
                                'required',
                            ],
                        ],
                        'hidden_fields' => [
                            [
                                'name',
                                'source',
                            ],
                        ],
                    ],
                    'guest' => [
                        'show_create_account',
                        'create_account_field' => [
                            'name',
                            'label',
                            'type',
                        ],
                        'password_field' => [
                            'name',
                            'label',
                            'type',
                        ],
                        'password_confirmation_field' => [
                            'name',
                            'label',
                            'type',
                        ],
                    ],
                    'customer' => [
                        'draft' => [
                            'name',
                            'full_name',
                            'first_name',
                            'last_name',
                            'email',
                            'phone',
                            'country',
                        ],
                    ],
                ],
                'district_shipping' => [
                    'carrier',
                    'title',
                    'description',
                    'district_field',
                    'default_country',
                    'districts',
                    'default_rate',
                    'configured_rates',
                    'dhaka_district',
                    'dhaka_title',
                    'dhaka_rate',
                    'outside_dhaka_title',
                    'outside_dhaka_rate',
                ],
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

    expect($response->json('data.single_flow'))->toBeTrue()
        ->and($response->json('data.cart.id'))->toBe($cart->id)
        ->and($response->json('data.cart.payment_method'))->toBe('cashondelivery')
        ->and($response->json('data.checkout.allow_guest_checkout'))->toBeTrue()
        ->and($response->json('data.customer.is_authenticated'))->toBeTrue()
        ->and($response->json('data.form.mode'))->toBe('customer')
        ->and($response->json('data.form.single_address.visible_fields.0.name'))->toBe('name')
        ->and($response->json('data.form.single_address.visible_fields.3.type'))->toBe('select')
        ->and($response->json('data.form.guest.show_create_account'))->toBeTrue()
        ->and($response->json('data.customer.draft.email'))->toBe($customer->email)
        ->and($response->json('data.customer.draft.name'))->toBe(trim($customer->first_name.' '.$customer->last_name))
        ->and($response->json('data.customer.draft.country'))->toBe('BD')
        ->and($response->json('data.district_shipping.default_country'))->toBe('BD')
        ->and($response->json('data.district_shipping.districts.0.name'))->toBe('Bagerhat')
        ->and($response->json('data.district_shipping.dhaka_district'))->toBe('Dhaka')
        ->and($response->json('data.payment_methods.0.method'))->toBe('cashondelivery')
        ->and(collect($response->json('data.payment_methods'))->pluck('method')->all())
        ->toContain('cashondelivery');
});

it('returns a guest checkout form contract with create account support', function () {
    $cart = $this->createCartWithItems('cashondelivery');

    $response = getJson(route('shop.checkout.custom.state'))
        ->assertOk();

    expect($response->json('data.customer.is_authenticated'))->toBeFalse()
        ->and($response->json('data.form.mode'))->toBe('guest')
        ->and($response->json('data.form.guest.show_create_account'))->toBeTrue()
        ->and($response->json('data.form.guest.create_account_field.name'))->toBe('create_account')
        ->and($response->json('data.form.guest.password_field.name'))->toBe('password')
        ->and($response->json('data.form.guest.password_confirmation_field.name'))->toBe('password_confirmation')
        ->and($response->json('data.form.single_address.visible_fields.0.name'))->toBe('name')
        ->and($response->json('data.cart.id'))->toBe($cart->id)
        ->and($response->json('data.cart.payment_method'))->toBe('cashondelivery')
        ->and($response->json('data.payment_methods.0.method'))->toBe('cashondelivery');
});
