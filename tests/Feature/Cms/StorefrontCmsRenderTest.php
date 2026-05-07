<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Platform\CommerceCore\Contracts\DataSourceResolverContract;
use Platform\ExperienceCms\Models\Page;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Faker\Helpers\Product as ProductFaker;
use Webkul\Shop\Http\Controllers\HomeController;
use Webkul\Shop\Tests\ShopTestCase;

uses(ShopTestCase::class);

beforeEach(function () {
    $this->withoutVite();

    $channel = core()->getCurrentChannel();
    $channel->update(['theme' => config('themes.shop-default')]);
    core()->setCurrentChannel($channel->fresh());
});

it('keeps the Bagisto storefront native by default', function () {
    expect(config('experience-cms'))->not->toHaveKey('storefront_mode')
        ->and(Route::has('platform.storefront.home_preview'))->toBeTrue()
        ->and(Route::has('platform.storefront.pages.show'))->toBeFalse()
        ->and(get_class(app(HomeController::class)))->toBe(HomeController::class);

    $this->get(route('shop.home.index'))
        ->assertOk()
        ->assertDontSee('gadget-header', false);
});

it('protects unsigned preview routes while allowing signed preview access', function () {
    $page = Page::query()->where('slug', 'home')->firstOrFail();

    $this->get(route('platform.storefront.home_preview'))
        ->assertForbidden();

    $signedPreviewUrl = URL::temporarySignedRoute('platform.storefront.home_preview', now()->addMinutes(30));

    $this->get($signedPreviewUrl)
        ->assertOk()
        ->assertSeeText('Structured CMS. Repeatable installs. Clean theme variation.');
});

it('renders published CMS homepage data through signed preview only', function () {
    $homePage = Page::query()->where('slug', 'home')->firstOrFail();
    $featuredSection = $homePage->sections()->whereHas('sectionType', fn ($query) => $query->where('code', 'featured_products'))->firstOrFail();

    $product = (new ProductFaker([
        'attributes' => [
            6 => 'featured',
        ],
        'attribute_value' => [
            'featured' => [
                'boolean_value' => true,
            ],
        ],
    ]))->getSimpleProductFactory()->create([
        'sku' => 'cms-home-product',
    ]);

    $featuredSection->update([
        'settings_json' => [
            'eyebrow' => 'Live Commerce Data',
            'limit' => 8,
        ],
        'data_source_type' => 'featured_products',
        'data_source_payload_json' => ['limit' => 8],
    ]);

    $signedPreviewUrl = URL::temporarySignedRoute('platform.storefront.home_preview', now()->addMinutes(30));

    $response = $this->get($signedPreviewUrl);

    $response->assertOk()
        ->assertSeeText('Structured CMS. Repeatable installs. Clean theme variation.')
        ->assertSee(route('shop.product_or_category.index', $product->url_key), false);
});

it('resolves featured products from the commerce-core data source resolver', function () {
    (new ProductFaker([
        'attributes' => [
            6 => 'featured',
        ],
        'attribute_value' => [
            'featured' => [
                'boolean_value' => true,
            ],
        ],
    ]))->getSimpleProductFactory()->create([
        'sku' => 'resolver-featured-product',
    ]);

    $items = app(DataSourceResolverContract::class)->resolve('featured_products', ['limit' => 8]);

    expect($items->pluck('sku'))->toContain('resolver-featured-product');
});

it('renders a native product page for a demo product', function () {
    $product = (new ProductFaker([
        'attributes' => [
            5 => 'new',
            6 => 'featured',
            11 => 'price',
            26 => 'guest_checkout',
        ],
        'attribute_value' => [
            'new' => [
                'boolean_value' => true,
            ],
            'featured' => [
                'boolean_value' => true,
            ],
            'price' => [
                'float_value' => rand(1000, 2000),
            ],
            'guest_checkout' => [
                'boolean_value' => true,
            ],
        ],
    ]))->getConfigurableProductFactory()->create();

    $response = $this->get(route('shop.product_or_category.index', $product->url_key));

    $response->assertOk()
        ->assertSeeText($product->name)
        ->assertDontSee('gadget-header', false);
});

it('renders a native category page for a demo category', function () {
    $category = app(CategoryRepository::class)->create([
        'locale' => 'all',
        'name' => 'Demo Shirts',
        'slug' => 'demo-shirts-native',
        'description' => 'Demo category page for storefront smoke testing.',
        'meta_title' => 'Demo Shirts',
        'meta_description' => 'Demo category page for storefront smoke testing.',
        'position' => 1,
        'status' => 1,
        'display_mode' => 'products_and_description',
        'parent_id' => 1,
        'attributes' => [],
    ]);

    $product = (new ProductFaker)->getSimpleProductFactory()->create([
        'sku' => 'demo-category-smoke-product',
    ]);

    $product->categories()->syncWithoutDetaching([$category->id]);

    $this->get(route('shop.product_or_category.index', $category->slug))
        ->assertOk()
        ->assertSeeText('Demo category page for storefront smoke testing.')
        ->assertSee('<v-category>', false)
        ->assertDontSee('gadget-header', false);
});
