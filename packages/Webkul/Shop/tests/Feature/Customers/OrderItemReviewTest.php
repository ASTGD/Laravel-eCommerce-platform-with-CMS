<?php

use Platform\CommerceCore\Models\ShipmentRecord;
use Platform\CommerceCore\Models\ShipmentRecordItem;
use Webkul\Core\Facades\SystemConfig as SystemConfigFacade;
use Webkul\Core\Models\CoreConfig;
use Webkul\Core\SystemConfig as BaseSystemConfig;
use Webkul\Customer\Models\Customer;
use Webkul\Faker\Helpers\Product as ProductFaker;
use Webkul\Product\Models\ProductReview;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderAddress;
use Webkul\Sales\Models\OrderItem;
use Webkul\Sales\Models\OrderPayment;

use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

beforeEach(function () {
    enableOrderItemReviewFlow();
});

it('shows write review for delivered order items', function () {
    $fixture = createOrderItemReviewFixture(delivered: true);

    $this->loginAsCustomer($fixture['customer']);

    get(route('shop.customers.account.orders.view', $fixture['order']->id))
        ->assertOk()
        ->assertSeeText(trans('shop::app.customers.account.orders.view.review.review'))
        ->assertSeeText(trans('shop::app.customers.account.orders.view.review.write-review'));
});

it('stores delivered order item reviews as pending moderation records', function () {
    $fixture = createOrderItemReviewFixture(delivered: true);

    $this->loginAsCustomer($fixture['customer']);

    post(route('shop.customers.account.reviews.order-item.store', [
        $fixture['order']->id,
        $fixture['item']->id,
    ]), [
        'rating' => 5,
        'title' => 'Great delivered product',
        'comment' => 'The product arrived and works as expected.',
    ])
        ->assertRedirect(route('shop.customers.account.orders.view', $fixture['order']->id))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('product_reviews', [
        'customer_id' => $fixture['customer']->id,
        'product_id' => $fixture['product']->id,
        'rating' => 5,
        'title' => 'Great delivered product',
        'status' => 'pending',
    ]);
});

it('rejects reviews before the item is delivered or completed', function () {
    $fixture = createOrderItemReviewFixture();

    $this->loginAsCustomer($fixture['customer']);

    post(route('shop.customers.account.reviews.order-item.store', [
        $fixture['order']->id,
        $fixture['item']->id,
    ]), [
        'rating' => 5,
        'title' => 'Too early',
        'comment' => 'This should not be accepted yet.',
    ])
        ->assertSessionHasErrors('review');

    $this->assertDatabaseMissing('product_reviews', [
        'customer_id' => $fixture['customer']->id,
        'product_id' => $fixture['product']->id,
    ]);
});

it('allows completed order items to be reviewed without a delivered shipment record', function () {
    $fixture = createOrderItemReviewFixture([
        'status' => Order::STATUS_COMPLETED,
    ]);

    $this->loginAsCustomer($fixture['customer']);

    get(route('shop.customers.account.orders.view', $fixture['order']->id))
        ->assertOk()
        ->assertSeeText(trans('shop::app.customers.account.orders.view.review.write-review'));
});

it('blocks duplicate reviews for the same customer and product', function () {
    $fixture = createOrderItemReviewFixture(delivered: true);

    ProductReview::factory()->create([
        'customer_id' => $fixture['customer']->id,
        'product_id' => $fixture['product']->id,
        'status' => 'pending',
    ]);

    $this->loginAsCustomer($fixture['customer']);

    post(route('shop.customers.account.reviews.order-item.store', [
        $fixture['order']->id,
        $fixture['item']->id,
    ]), [
        'rating' => 4,
        'title' => 'Second review',
        'comment' => 'A second review should be blocked.',
    ])
        ->assertSessionHasErrors('review');

    expect(ProductReview::query()
        ->where('customer_id', $fixture['customer']->id)
        ->where('product_id', $fixture['product']->id)
        ->count())->toBe(1);
});

it('rejects product review api submissions from customers without a qualifying delivered purchase', function () {
    $fixture = createOrderItemReviewFixture();

    $this->loginAsCustomer($fixture['customer']);

    postJson(route('shop.api.products.reviews.store', $fixture['product']->id), [
        'rating' => 5,
        'title' => 'API review',
        'comment' => 'This is not eligible yet.',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('review');

    $this->assertDatabaseMissing('product_reviews', [
        'customer_id' => $fixture['customer']->id,
        'product_id' => $fixture['product']->id,
    ]);
});

function enableOrderItemReviewFlow(): void
{
    foreach ([
        'catalog.products.review.customer_review',
        'general.admin_modules.visibility.customer_reviews_enabled',
    ] as $code) {
        CoreConfig::query()->updateOrCreate(
            [
                'code' => $code,
                'channel_code' => null,
                'locale_code' => null,
            ],
            [
                'value' => '1',
            ],
        );
    }

    app()->forgetInstance(BaseSystemConfig::class);
    SystemConfigFacade::clearResolvedInstance(BaseSystemConfig::class);
}

function createOrderItemReviewFixture(array $orderOverrides = [], bool $delivered = false): array
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

    $customer = Customer::factory()->create();

    $order = Order::factory()->create(array_merge([
        'customer_id' => $customer->id,
        'customer_email' => $customer->email,
        'customer_first_name' => $customer->first_name,
        'customer_last_name' => $customer->last_name,
        'status' => Order::STATUS_PROCESSING,
    ], $orderOverrides));

    $item = OrderItem::factory()->create([
        'product_id' => $product->id,
        'order_id' => $order->id,
        'sku' => $product->sku,
        'type' => $product->type,
        'name' => $product->name,
        'qty_ordered' => 1,
        'qty_canceled' => 0,
        'qty_refunded' => 0,
    ]);

    OrderAddress::factory()->create([
        'customer_id' => $customer->id,
        'order_id' => $order->id,
        'address_type' => OrderAddress::ADDRESS_TYPE_BILLING,
    ]);

    OrderAddress::factory()->create([
        'customer_id' => $customer->id,
        'order_id' => $order->id,
        'address_type' => OrderAddress::ADDRESS_TYPE_SHIPPING,
    ]);

    OrderPayment::factory()->create([
        'order_id' => $order->id,
    ]);

    if ($delivered) {
        $shipmentRecord = ShipmentRecord::query()->create([
            'order_id' => $order->id,
            'status' => ShipmentRecord::STATUS_DELIVERED,
            'carrier_name_snapshot' => 'Test Courier',
            'tracking_number' => 'TRACK-REVIEW-001',
            'delivered_at' => now(),
        ]);

        ShipmentRecordItem::query()->create([
            'shipment_record_id' => $shipmentRecord->id,
            'order_item_id' => $item->id,
            'name' => $item->name,
            'sku' => $item->sku,
            'qty' => 1,
        ]);
    }

    return [
        'customer' => $customer,
        'product' => $product,
        'order' => $order->fresh(['items.product', 'addresses', 'payment']),
        'item' => $item->fresh(['order', 'product']),
    ];
}
