<?php

use Platform\CommerceCore\Models\PickupPoint;
use Webkul\Checkout\Models\Cart;
use Webkul\Checkout\Models\CartItem;
use Webkul\Core\Models\CoreConfig;
use Webkul\Customer\Models\Customer;
use Webkul\Faker\Helpers\Product as ProductFaker;
use Webkul\Sales\Models\Order;
use Webkul\Shipping\Facades\Shipping;

use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

function setCourierPickupConfig(string $code, mixed $value): void
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

function createPickupCheckoutContext(): array
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
        'first_name' => 'Pickup',
        'last_name' => 'Customer',
        'email' => 'pickup.customer@example.com',
        'phone' => '+8801711111111',
    ]);

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
        'quantity' => 1,
        'name' => $product->name,
        'price' => $convertedPrice = core()->convertPrice($price = $product->price),
        'price_incl_tax' => $convertedPrice,
        'base_price' => $price,
        'base_price_incl_tax' => $price,
        'total' => $convertedPrice,
        'total_incl_tax' => $convertedPrice,
        'base_total' => $price,
        'weight' => $product->weight ?? 0,
        'total_weight' => $product->weight ?? 0,
        'base_total_weight' => $product->weight ?? 0,
        'type' => $product->type,
        'additional' => $additional,
    ]);

    cart()->setCart($cart);

    cart()->saveAddresses([
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
            'phone' => $customer->phone,
        ],
        'shipping' => [],
    ]);

    Shipping::collectRates();

    return [$customer, $cart];
}

beforeEach(function () {
    setCourierPickupConfig('sales.carriers.courier.active', 1);
    setCourierPickupConfig('sales.carriers.courier.title', 'Courier');
    setCourierPickupConfig('sales.carriers.courier.description', 'Bangladesh courier rates');
    setCourierPickupConfig('sales.carriers.courier.home_delivery_active', 1);
    setCourierPickupConfig('sales.carriers.courier.home_delivery_title', 'Home Delivery');
    setCourierPickupConfig('sales.carriers.courier.home_delivery_rate', 120);
    setCourierPickupConfig('sales.carriers.courier.pickup_active', 1);
    setCourierPickupConfig('sales.carriers.courier.pickup_title', 'Courier Pick-up');
    setCourierPickupConfig('sales.carriers.courier.pickup_rate', 60);
    setCourierPickupConfig('sales.payment_methods.cashondelivery.active', 1);
});

it('requires an active pickup point before courier pick-up can move checkout forward', function () {
    [$customer] = createPickupCheckoutContext();

    $this->loginAsCustomer($customer);

    postJson(route('shop.checkout.onepage.shipping_methods.store'), [
        'shipping_method' => 'courier_pickup',
    ])->assertUnprocessable()
        ->assertJsonValidationErrorFor('pickup_point_id');
});

it('stores the selected pickup point on the order and shows it in the customer order view', function () {
    [$customer] = createPickupCheckoutContext();

    $pickupPoint = PickupPoint::query()->create([
        'code' => 'banani-pickup',
        'name' => 'Banani Pick-up Point',
        'slug' => 'banani-pickup',
        'courier_name' => 'ASTGD Courier',
        'address_line_1' => 'Road 11, Banani',
        'city' => 'Dhaka',
        'country' => 'BD',
        'phone' => '+8801712345678',
        'is_active' => true,
    ]);

    $this->loginAsCustomer($customer);

    postJson(route('shop.checkout.onepage.shipping_methods.store'), [
        'shipping_method' => 'courier_pickup',
        'pickup_point_id' => $pickupPoint->id,
    ])->assertOk();

    postJson(route('shop.checkout.onepage.payment_methods.store'), [
        'payment' => [
            'method' => 'cashondelivery',
        ],
    ])->assertOk();

    postJson(route('shop.checkout.onepage.orders.store'))
        ->assertOk()
        ->assertJsonPath('data.redirect', true);

    $order = Order::query()
        ->where('customer_id', $customer->id)
        ->latest('id')
        ->firstOrFail();

    expect($order->shipping_address->pickup_point_id)->toBe($pickupPoint->id)
        ->and(data_get($order->shipping_address->additional, 'pickup_point.name'))->toBe('Banani Pick-up Point');

    get(route('shop.customers.account.orders.view', $order->id))
        ->assertOk()
        ->assertSeeText('Pick-up Point')
        ->assertSeeText('Banani Pick-up Point')
        ->assertSeeText('ASTGD Courier');
});
