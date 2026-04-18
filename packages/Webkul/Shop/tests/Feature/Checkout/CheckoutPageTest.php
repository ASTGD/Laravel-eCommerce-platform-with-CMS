<?php

use Webkul\Checkout\Models\Cart;
use Webkul\Checkout\Models\CartItem;
use Webkul\Customer\Models\Customer;
use Webkul\Core\Models\CoreConfig;
use Webkul\Faker\Helpers\Product as ProductFaker;
use Webkul\Sales\Models\Order;

use function Pest\Laravel\get;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

function setCheckoutPageConfig(string $code, mixed $value): void
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

    getJson(route('shop.checkout.custom.state'))
        ->assertSuccessful()
        ->assertJsonPath('data.customer.is_authenticated', true)
        ->assertJsonPath('data.customer.draft.name', trim($customer->first_name.' '.$customer->last_name))
        ->assertJsonPath('data.customer.draft.email', $customer->email)
        ->assertJsonPath('data.customer.draft.phone', $customer->phone);
});

it('opens checkout on the requested payment step after a failed online payment retry', function () {
    [$customer] = createCheckoutCustomerAndCart();

    $this->loginAsCustomer($customer);

    get(route('shop.checkout.custom.index', ['step' => 'payment']))
        ->assertOk()
        ->assertSee('currentStep: "payment"', false);
});

it('renders the simplified checkout form contract for authenticated customers', function () {
    [$customer] = createCheckoutCustomerAndCart();

    $this->loginAsCustomer($customer);

    get(route('shop.checkout.custom.index'))
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

    getJson(route('shop.checkout.custom.state'))
        ->assertOk()
        ->assertJsonPath('data.cart.payment_method', 'cashondelivery');

    get(route('shop.checkout.custom.index'))
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

    get(route('shop.checkout.custom.success'))
        ->assertOk()
        ->assertSeeText('See Order')
        ->assertSee(route('shop.customers.account.orders.view', $order->id), false);
});

it('lets a guest submit the single checkout flow and reach the success redirect', function () {
    setCheckoutPageConfig('sales.checkout.shopping_cart.allow_guest_checkout', 1);
    setCheckoutPageConfig('sales.carriers.courier.active', 1);
    setCheckoutPageConfig('sales.carriers.courier.title', 'Courier');
    setCheckoutPageConfig('sales.carriers.courier.description', 'District-based delivery charges');
    setCheckoutPageConfig('sales.carriers.courier.default_rate', 120);
    setCheckoutPageConfig('sales.carriers.courier.district_rates', "Dhaka=60\nRajshahi=140");
    setCheckoutPageConfig('sales.carriers.courier.dhaka_district', 'Dhaka');
    setCheckoutPageConfig('sales.carriers.courier.dhaka_title', 'Dhaka Delivery');
    setCheckoutPageConfig('sales.carriers.courier.dhaka_rate', 60);
    setCheckoutPageConfig('sales.carriers.courier.outside_dhaka_title', 'Outside Dhaka Delivery');
    setCheckoutPageConfig('sales.carriers.courier.outside_dhaka_rate', 120);
    setCheckoutPageConfig('sales.payment_methods.cashondelivery.active', 1);

    createGuestCheckoutCart();

    getJson(route('shop.checkout.custom.state'))
        ->assertOk()
        ->assertJsonPath('data.cart.payment_method', 'cashondelivery');

    postJson(route('shop.checkout.custom.addresses.store'), [
        'billing' => [
            'use_for_shipping' => true,
            'first_name' => 'Guest',
            'last_name' => 'Checkout',
            'email' => 'guest.checkout@example.com',
            'address' => ['House 12'],
            'country' => 'BD',
            'state' => 'Dhaka',
            'city' => 'Dhaka',
            'postcode' => '1212',
            'phone' => '+8801711111111',
        ],
    ])
        ->assertOk()
        ->assertJsonPath('data.payment_methods.0.method', 'cashondelivery');

    getJson(route('shop.checkout.custom.state'))
        ->assertOk()
        ->assertJsonPath('data.cart.shipping_method', 'courier_dhaka')
        ->assertJsonPath('data.cart.payment_method', 'cashondelivery')
        ->assertJsonPath('data.cart.billing_address.state', 'Dhaka')
        ->assertJsonPath('data.cart.shipping_address.state', 'Dhaka');

    postJson(route('shop.checkout.custom.orders.store'))
        ->assertOk()
        ->assertJsonPath('data.redirect', true)
        ->assertJsonPath('data.redirect_url', fn (string $url) => str_contains($url, route('shop.checkout.success', absolute: false)));
});

it('lets a guest complete the custom one page checkout even when courier is inactive', function () {
    setCheckoutPageConfig('sales.checkout.shopping_cart.allow_guest_checkout', 1);
    setCheckoutPageConfig('sales.carriers.courier.active', 0);
    setCheckoutPageConfig('sales.carriers.courier.title', 'Courier');
    setCheckoutPageConfig('sales.carriers.courier.description', 'District-based delivery charges');
    setCheckoutPageConfig('sales.carriers.courier.default_rate', 120);
    setCheckoutPageConfig('sales.carriers.courier.district_rates', "Dhaka=60\nRajshahi=140");
    setCheckoutPageConfig('sales.carriers.courier.dhaka_district', 'Dhaka');
    setCheckoutPageConfig('sales.carriers.courier.dhaka_title', 'Dhaka Delivery');
    setCheckoutPageConfig('sales.carriers.courier.dhaka_rate', 60);
    setCheckoutPageConfig('sales.carriers.courier.outside_dhaka_title', 'Outside Dhaka Delivery');
    setCheckoutPageConfig('sales.carriers.courier.outside_dhaka_rate', 120);
    setCheckoutPageConfig('sales.payment_methods.cashondelivery.active', 1);

    createGuestCheckoutCart();

    getJson(route('shop.checkout.custom.state'))
        ->assertOk()
        ->assertJsonPath('data.cart.payment_method', 'cashondelivery');

    postJson(route('shop.checkout.custom.addresses.store'), [
        'billing' => [
            'use_for_shipping' => true,
            'first_name' => 'Guest',
            'last_name' => 'Checkout',
            'email' => 'guest.checkout@example.com',
            'address' => ['House 12'],
            'country' => 'BD',
            'state' => 'Dhaka',
            'city' => 'Dhaka',
            'postcode' => '1212',
            'phone' => '+8801711111111',
        ],
    ])
        ->assertOk()
        ->assertJsonPath('data.shippingMethods.courier.rates.0.method', 'courier_dhaka');

    postJson(route('shop.checkout.custom.orders.store'))
        ->assertOk()
        ->assertJsonPath('data.redirect', true)
        ->assertJsonPath('data.redirect_url', fn (string $url) => str_contains($url, route('shop.checkout.success', absolute: false)));
});

it('creates a real customer account from one page checkout when create account is selected', function () {
    $phone = '+88017'.str_pad((string) random_int(1000000, 9999999), 7, '0', STR_PAD_LEFT);

    setCheckoutPageConfig('sales.checkout.shopping_cart.allow_guest_checkout', 1);
    setCheckoutPageConfig('sales.carriers.courier.active', 1);
    setCheckoutPageConfig('sales.carriers.courier.default_rate', 120);
    setCheckoutPageConfig('sales.carriers.courier.district_rates', "Dhaka=60\nRajshahi=140");
    setCheckoutPageConfig('sales.payment_methods.cashondelivery.active', 1);

    createGuestCheckoutCart();

    getJson(route('shop.checkout.custom.state'))
        ->assertOk()
        ->assertJsonPath('data.cart.payment_method', 'cashondelivery');

    postJson(route('shop.checkout.custom.addresses.store'), [
        'billing' => [
            'use_for_shipping' => true,
            'first_name' => 'Shafin',
            'last_name' => 'Mia',
            'email' => 'sharwatshafin1000@gmail.com',
            'address' => ['House 12'],
            'country' => 'BD',
            'state' => 'Dhaka',
            'city' => 'Dhaka',
            'postcode' => '1212',
            'phone' => $phone,
            'create_account' => true,
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ],
    ])->assertOk();

    postJson(route('shop.checkout.custom.orders.store'))
        ->assertOk()
        ->assertJsonPath('data.redirect', true);

    $customer = Customer::query()
        ->where('email', 'sharwatshafin1000@gmail.com')
        ->first();

    expect($customer)->not->toBeNull()
        ->and(auth()->guard('customer')->check())->toBeTrue()
        ->and(auth()->guard('customer')->user()->id)->toBe($customer->id);

    $order = Order::query()->latest('id')->first();

    expect($order)->not->toBeNull()
        ->and($order->customer_id)->toBe($customer->id)
        ->and((int) $order->is_guest)->toBe(0);
});

it('attaches a guest one page checkout order to an existing customer when the email matches', function () {
    $email = 'existing.'.uniqid().'@example.com';
    $phone = '+88017'.str_pad((string) random_int(1000000, 9999999), 7, '0', STR_PAD_LEFT);
    $checkoutPhone = '+88018'.str_pad((string) random_int(1000000, 9999999), 7, '0', STR_PAD_LEFT);

    setCheckoutPageConfig('sales.checkout.shopping_cart.allow_guest_checkout', 1);
    setCheckoutPageConfig('sales.carriers.courier.active', 1);
    setCheckoutPageConfig('sales.carriers.courier.default_rate', 120);
    setCheckoutPageConfig('sales.carriers.courier.district_rates', "Dhaka=60\nRajshahi=140");
    setCheckoutPageConfig('sales.payment_methods.cashondelivery.active', 1);

    $customer = Customer::factory()->create([
        'first_name' => 'Shafin',
        'last_name' => 'Mia',
        'email' => $email,
        'phone' => $phone,
    ]);

    createGuestCheckoutCart();

    postJson(route('shop.checkout.custom.addresses.store'), [
        'billing' => [
            'use_for_shipping' => true,
            'first_name' => 'Shafin',
            'last_name' => 'Mia',
            'email' => $email,
            'address' => ['House 58 Khansamarchock'],
            'country' => 'BD',
            'state' => 'Rajshahi',
            'city' => 'Rajshahi',
            'postcode' => '6200',
            'phone' => $checkoutPhone,
            'create_account' => false,
        ],
    ])->assertOk();

    postJson(route('shop.checkout.custom.orders.store'))
        ->assertOk()
        ->assertJsonPath('data.redirect', true);

    $order = Order::query()->latest('id')->first();

    expect($order)->not->toBeNull()
        ->and($order->customer_id)->toBe($customer->id)
        ->and((int) $order->is_guest)->toBe(0)
        ->and(auth()->guard('customer')->check())->toBeFalse()
        ->and($customer->fresh()->orders()->whereKey($order->id)->exists())->toBeTrue();
});

it('keeps a guest one page checkout order as guest when only the phone matches an existing customer', function () {
    $existingEmail = 'existing.'.uniqid().'@example.com';
    $checkoutEmail = 'guest.'.uniqid().'@example.com';
    $phone = '+88017'.str_pad((string) random_int(1000000, 9999999), 7, '0', STR_PAD_LEFT);

    setCheckoutPageConfig('sales.checkout.shopping_cart.allow_guest_checkout', 1);
    setCheckoutPageConfig('sales.carriers.courier.active', 1);
    setCheckoutPageConfig('sales.carriers.courier.default_rate', 120);
    setCheckoutPageConfig('sales.carriers.courier.district_rates', "Dhaka=60\nRajshahi=140");
    setCheckoutPageConfig('sales.payment_methods.cashondelivery.active', 1);

    Customer::factory()->create([
        'first_name' => 'Existing',
        'last_name' => 'Customer',
        'email' => $existingEmail,
        'phone' => $phone,
    ]);

    createGuestCheckoutCart();

    postJson(route('shop.checkout.custom.addresses.store'), [
        'billing' => [
            'use_for_shipping' => true,
            'first_name' => 'Guest',
            'last_name' => 'Checkout',
            'email' => $checkoutEmail,
            'address' => ['House 58 Khansamarchock'],
            'country' => 'BD',
            'state' => 'Rajshahi',
            'city' => 'Rajshahi',
            'postcode' => '6200',
            'phone' => $phone,
            'create_account' => false,
        ],
    ])->assertOk();

    postJson(route('shop.checkout.custom.orders.store'))
        ->assertOk()
        ->assertJsonPath('data.redirect', true);

    $order = Order::query()->latest('id')->first();

    expect($order)->not->toBeNull()
        ->and($order->customer_id)->toBeNull()
        ->and((int) $order->is_guest)->toBe(1);
});

it('updates one page checkout item quantities through the existing cart update flow', function () {
    createGuestCheckoutCart();

    $cart = Cart::query()->latest('id')->with('items')->firstOrFail();
    $item = $cart->items->first();

    putJson(route('shop.api.checkout.cart.update'), [
        'qty' => [
            $item->id => 3,
        ],
    ])
        ->assertOk()
        ->assertJsonPath('data.items.0.quantity', 3);

    $item->refresh();

    expect($item->quantity)->toBe(3);
});
