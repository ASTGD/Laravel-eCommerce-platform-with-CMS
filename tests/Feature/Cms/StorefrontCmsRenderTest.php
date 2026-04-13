<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\URL;
use Platform\CommerceCore\Contracts\DataSourceResolverContract;
use Platform\ExperienceCms\Models\Page;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Faker\Helpers\Product as ProductFaker;
use Webkul\Product\Repositories\ProductImageRepository;
use Webkul\Shop\Tests\ShopTestCase;
use Webkul\Shop\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

uses(ShopTestCase::class);

beforeEach(function () {
    $this->withoutVite();
});

it('keeps the Bagisto storefront native by default', function () {
    expect(config('experience-cms.storefront_mode'))->toBe('native')
        ->and(Route::has('platform.storefront.home_preview'))->toBeFalse()
        ->and(Route::has('platform.storefront.pages.show'))->toBeFalse()
        ->and(get_class(app(HomeController::class)))->toBe(HomeController::class);

    $this->get(route('shop.home.index'))->assertOk();
});

it('protects unsigned preview routes while allowing signed preview access', function () {
    if (config('experience-cms.storefront_mode') !== 'cms') {
        $this->markTestSkipped('CMS storefront mode is disabled by default.');
    }

    $page = Page::query()->where('slug', 'home')->firstOrFail();

    $this->get(route('platform.storefront.home_preview'))
        ->assertForbidden();

    $signedPreviewUrl = URL::temporarySignedRoute('platform.storefront.home_preview', now()->addMinutes(30));

    $this->get($signedPreviewUrl)
        ->assertOk()
        ->assertSeeText('Structured CMS. Repeatable installs. Clean theme variation.');
});

it('renders the published homepage from CMS data through the storefront route', function () {
    if (config('experience-cms.storefront_mode') !== 'cms') {
        $this->markTestSkipped('CMS storefront mode is disabled by default.');
    }

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

    $response = $this->get(route('shop.home.index'));

    $response->assertOk()
        ->assertSeeText('Structured CMS. Repeatable installs. Clean theme variation.')
        ->assertSeeText($product->sku)
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

it('renders configurable shirt options and storage-backed images on the product page', function () {
    if (config('experience-cms.storefront_mode') !== 'cms') {
        $this->markTestSkipped('CMS storefront mode is disabled by default.');
    }

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

    app(ProductImageRepository::class)->upload([
        'images' => [
            'files' => [
                UploadedFile::fake()->image('shirt.jpg', 1200, 1200),
            ],
        ],
    ], $product, 'images');

    $response = $this->get(route('shop.product_or_category.index', $product->url_key));

    $response->assertOk()
        ->assertSee('data-configurable-options="product-'.$product->id.'"', false)
        ->assertSee('data-configurable-cart-form="product-'.$product->id.'"', false)
        ->assertSee('data-configurable-selection-input', false)
        ->assertSee('selected_configurable_option', false)
        ->assertSee('/storage/product/'.$product->id.'/', false)
        ->assertSeeText('Choose the shirt size and color before adding it to the cart.');
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
        ->assertSee('<v-category>', false);
});
