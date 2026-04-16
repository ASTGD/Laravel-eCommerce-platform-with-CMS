<?php

use Webkul\Core\Models\CoreConfig;
use Webkul\Faker\Helpers\Product as ProductFaker;

use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

function setBuyNowCheckoutConfig(string $code, mixed $value): void
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

it('shows the buy now action on the product page when the storefront toggle is not configured', function () {
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

    setBuyNowCheckoutConfig('sales.checkout.shopping_cart.cart_page', 1);

    CoreConfig::query()
        ->where('code', 'catalog.products.storefront.buy_now_button_display')
        ->delete();

    get(route('shop.product_or_category.index', $product->url_key))
        ->assertOk()
        ->assertSee('title="Add To Cart"', false)
        ->assertSee('title="Buy Now"', false);
});

it('redirects directly to checkout when buy now is used', function () {
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

    setBuyNowCheckoutConfig('sales.checkout.shopping_cart.cart_page', 1);

    postJson(route('shop.api.checkout.cart.store'), [
        'product_id' => $product->id,
        'is_buy_now' => 1,
        'quantity' => 1,
    ])
        ->assertOk()
        ->assertJsonPath('redirect', route('shop.checkout.onepage.index'))
        ->assertJsonPath('data.items_count', 1);
});
