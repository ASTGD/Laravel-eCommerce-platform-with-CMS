<?php

use Illuminate\Support\Facades\URL;
use Platform\CommerceCore\Contracts\DataSourceResolverContract;
use Platform\ExperienceCms\Models\Page;
use Webkul\Faker\Helpers\Product as ProductFaker;
use Webkul\Shop\Tests\ShopTestCase;

uses(ShopTestCase::class);

it('protects unsigned preview routes while allowing signed preview access', function () {
    $page = Page::query()->where('slug', 'home')->firstOrFail();

    $this->get(route('platform.storefront.home_preview'))
        ->assertForbidden();

    $signedPreviewUrl = URL::temporarySignedRoute('platform.storefront.home_preview', now()->addMinutes(30));

    $this->get($signedPreviewUrl)
        ->assertOk()
        ->assertSeeText('Structured CMS. Repeatable installs. Clean theme variation.');
});

it('renders the published homepage from CMS data through the storefront route', function () {
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
        ->assertSeeText($product->sku);
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
