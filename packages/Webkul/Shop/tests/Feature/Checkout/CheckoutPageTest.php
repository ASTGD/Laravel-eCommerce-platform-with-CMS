<?php

use Webkul\Checkout\Models\Cart;
use Webkul\Checkout\Models\CartItem;
use Webkul\Customer\Models\Customer;
use Webkul\Faker\Helpers\Product as ProductFaker;
use Webkul\Sales\Models\Order;

use function Pest\Laravel\get;
use function Pest\Laravel\getJson;

function createCheckoutCustomerAndCart(array $customerOverrides = []): array
{
    $product = (new ProductFaker([
        'attributes' => [
            5 => 'new',
        ],

        'attribute_value' => [
            'new' => [
                'boolean_value' => true,
            ],
        ],
    ]))
        ->getSimpleProductFactory()
        ->create();

    $customer = Customer::factory()->create([
        'first_name' => 'Checkout',
        'last_name' => 'Customer',
        'email' => 'checkout.customer@example.com',
        'phone' => '+8801712345678',
    ] + $customerOverrides);

    $cart = Cart::factory()->create([
        'customer_id' => $customer->id,
        'customer_first_name' => $customer->first_name,
        'customer_last_name' => $customer->last_name,
        'customer_email' => $customer->email,
        'is_guest' => 0,
    ]);

    $additional = [
        'product_id' => $product->id,
        'rating' => '0',
        'is_buy_now' => '0',
        'quantity' => '1',
    ];

    CartItem::factory()->create([
        'cart_id' => $cart->id,
        'product_id' => $product->id,
        'sku' => $product->sku,
        'quantity' => $additional['quantity'],
        'name' => $product->name,
        'price' => $convertedPrice = core()->convertPrice($price = $product->price),
        'price_incl_tax' => $convertedPrice,
        'base_price' => $price,
        'base_price_incl_tax' => $price,
        'total' => $total = $convertedPrice * $additional['quantity'],
        'total_incl_tax' => $total,
        'base_total' => $price * $additional['quantity'],
        'weight' => $product->weight ?? 0,
        'total_weight' => ($product->weight ?? 0) * $additional['quantity'],
        'base_total_weight' => ($product->weight ?? 0) * $additional['quantity'],
        'type' => $product->type,
        'additional' => $additional,
    ]);

    cart()->setCart($cart);

    return [$customer, $cart];
}

function createGuestCheckoutCart(): Cart
{
    $product = (new ProductFaker([
        'attributes' => [
            5 => 'new',
            26 => 'guest_checkout',
        ],

        'attribute_value' => [
            'new' => [
                'boolean_value' => true,
            ],

            'guest_checkout' => [
                'boolean_value' => true,
            ],
        ],
    ]))
        ->getSimpleProductFactory()
        ->create();

    $cart = Cart::factory()->create([
        'is_guest' => 1,
    ]);

    $additional = [
        'product_id' => $product->id,
        'rating' => '0',
        'is_buy_now' => '0',
        'quantity' => '1',
    ];

    CartItem::factory()->create([
        'cart_id' => $cart->id,
        'product_id' => $product->id,
        'sku' => $product->sku,
        'quantity' => $additional['quantity'],
        'name' => $product->name,
        'price' => $convertedPrice = core()->convertPrice($price = $product->price),
        'price_incl_tax' => $convertedPrice,
        'base_price' => $price,
        'base_price_incl_tax' => $price,
        'total' => $total = $convertedPrice * $additional['quantity'],
        'total_incl_tax' => $total,
        'base_total' => $price * $additional['quantity'],
        'weight' => $product->weight ?? 0,
        'total_weight' => ($product->weight ?? 0) * $additional['quantity'],
        'base_total_weight' => ($product->weight ?? 0) * $additional['quantity'],
        'type' => $product->type,
        'additional' => $additional,
    ]);

    cart()->setCart($cart);

    cart()->collectTotals();

    return $cart;
}

it('prefills checkout address details for logged in customers without saved addresses', function () {
    [$customer] = createCheckoutCustomerAndCart();

    $this->loginAsCustomer($customer);

    getJson(route('shop.checkout.onepage.state'))
        ->assertSuccessful()
        ->assertJsonPath('data.customer.is_authenticated', true)
        ->assertJsonPath('data.customer.draft.name', trim($customer->first_name.' '.$customer->last_name))
        ->assertJsonPath('data.customer.draft.email', $customer->email)
        ->assertJsonPath('data.customer.draft.phone', $customer->phone);
});

it('opens checkout on the requested payment step after a failed online payment retry', function () {
    [$customer] = createCheckoutCustomerAndCart();

    $this->loginAsCustomer($customer);

    get(route('shop.checkout.onepage.index', ['step' => 'payment']))
        ->assertOk()
        ->assertSee('currentStep: "payment"', false);
});

it('renders the simplified checkout form contract for authenticated customers', function () {
    [$customer] = createCheckoutCustomerAndCart();

    $this->loginAsCustomer($customer);

    get(route('shop.checkout.onepage.index'))
        ->assertOk()
        ->assertSeeText('Your Order')
        ->assertDontSeeText('Cart Summary')
        ->assertSeeText('Name')
        ->assertSeeText('Mobile Number')
        ->assertSeeText('District / Region')
        ->assertSeeText('Full Address')
        ->assertSeeText('Email')
        ->assertDontSeeText('Company Name')
        ->assertDontSeeText('Vat ID')
        ->assertDontSeeText('Proceed');
});

it('renders the checkout page for guest customers from buy now', function () {
    createGuestCheckoutCart();

    get(route('shop.checkout.onepage.index'))
        ->assertOk()
        ->assertSeeText('Returning customer?')
        ->assertSeeText('Have a coupon?')
        ->assertSeeText('Billing & Shipping')
        ->assertSeeText('Your Order')
        ->assertSeeText('Name')
        ->assertSeeText('Mobile Number')
        ->assertSeeText('District / Region')
        ->assertSeeText('Full Address')
        ->assertSeeText('Email')
        ->assertDontSeeText('Proceed')
        ->assertSeeText('Payment Method');
});

it('shows a see order button on checkout success for logged in customers', function () {
    [$customer] = createCheckoutCustomerAndCart();

    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'customer_email' => $customer->email,
        'customer_first_name' => $customer->first_name,
        'customer_last_name' => $customer->last_name,
        'customer_type' => Customer::class,
        'cart_id' => 9999,
        'increment_id' => '2000001',
    ]);

    $this->loginAsCustomer($customer);

    session(['order_id' => $order->id]);

    get(route('shop.checkout.onepage.success'))
        ->assertOk()
        ->assertSeeText('See Order')
        ->assertSee(route('shop.customers.account.orders.view', $order->id), false);
});
