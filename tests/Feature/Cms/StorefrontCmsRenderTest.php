<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Platform\CommerceCore\Contracts\DataSourceResolverContract;
use Platform\ExperienceCms\Models\FooterConfig;
use Platform\ExperienceCms\Models\HeaderConfig;
use Platform\ExperienceCms\Models\Menu;
use Platform\ExperienceCms\Models\MenuItem;
use Platform\ExperienceCms\Models\Page;
use Platform\ExperienceCms\Models\PageSection;
use Platform\ExperienceCms\Models\SectionType;
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
        ->assertOk();
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
        ->assertSee(route('shop.product_or_category.index', $product->url_key), false);
});

it('renders CMS hero slider slides through the signed homepage preview', function () {
    $homePage = Page::query()->where('slug', 'home')->firstOrFail();
    $heroAreaId = $homePage->template?->areas()->where('code', 'hero')->value('id')
        ?? $homePage->template?->areas()->orderBy('sort_order')->value('id');

    $sectionType = SectionType::query()->updateOrCreate(
        ['code' => 'hero_slider'],
        [
            'name' => 'Hero Slider',
            'category' => 'hero',
            'config_schema_json' => [],
            'supports_components' => false,
            'allowed_data_sources_json' => [],
            'renderer_class' => null,
            'is_active' => true,
        ]
    );

    PageSection::query()->create([
        'page_id' => $homePage->id,
        'template_area_id' => $heroAreaId,
        'section_type_id' => $sectionType->id,
        'sort_order' => 0,
        'title' => 'Homepage Slider',
        'settings_json' => [
            'slides' => [
                [
                    'image' => 'storage/cms/homepage/hero-slider/slide-one.jpg',
                    'title' => 'CMS hero slide one',
                    'link' => '/sale',
                ],
            ],
        ],
        'is_active' => true,
    ]);

    $signedPreviewUrl = URL::temporarySignedRoute('platform.storefront.home_preview', now()->addMinutes(30));

    $this->get($signedPreviewUrl)
        ->assertOk()
        ->assertSee('slide-one.jpg', false)
        ->assertSee('CMS hero slide one', false)
        ->assertSee('<v-carousel', false);
});

it('renders the CMS hero through the active storefront theme', function (string $theme) {
    $channel = core()->getCurrentChannel();
    $channel->update(['theme' => $theme]);
    core()->setCurrentChannel($channel->fresh());

    $homePage = Page::query()->where('slug', 'home')->firstOrFail();
    $heroAreaId = $homePage->template?->areas()->where('code', 'hero')->value('id')
        ?? $homePage->template?->areas()->orderBy('sort_order')->value('id');

    $sectionType = SectionType::query()->updateOrCreate(
        ['code' => 'hero'],
        [
            'name' => 'Hero',
            'category' => 'hero',
            'config_schema_json' => [],
            'supports_components' => false,
            'allowed_data_sources_json' => [],
            'renderer_class' => null,
            'is_active' => true,
        ]
    );

    $homePage->sections()->whereHas('sectionType', fn ($query) => $query->whereIn('code', ['hero', 'hero_slider', 'hero_banner']))->delete();

    PageSection::query()->create([
        'page_id' => $homePage->id,
        'template_area_id' => $heroAreaId,
        'section_type_id' => $sectionType->id,
        'sort_order' => 0,
        'title' => 'Homepage Hero',
        'settings_json' => [
            'mode' => 'static',
            'slides' => [
                [
                    'image' => 'storage/cms/homepage/hero/cms-theme-hero.jpg',
                    'title' => 'CMS active theme hero',
                    'tag' => 'CMS Launch',
                    'headline' => 'Build your',
                    'highlight' => 'next hero.',
                    'body' => 'CMS hero body copy',
                    'badge' => 'CMS badge',
                    'primary_cta_label' => 'Shop CMS',
                    'primary_cta_url' => '/cms-sale',
                    'secondary_cta_label' => 'Learn more',
                    'secondary_cta_url' => '/cms-story',
                ],
            ],
        ],
        'is_active' => true,
    ]);

    $this->get(route('shop.home.index'))
        ->assertOk()
        ->assertSee('CMS Launch', false)
        ->assertSee('Build your', false)
        ->assertSee('next hero.', false)
        ->assertSee('CMS hero body copy', false)
        ->assertSee('cms-theme-hero.jpg', false)
        ->assertSee('CMS badge', false)
        ->assertSee('Shop CMS', false)
        ->assertSee('Learn more', false);
})->with([
    ['gadget'],
    ['clothing'],
]);

it('renders highlighted CMS hero text inline without duplicating the highlighted words', function (string $theme) {
    $channel = core()->getCurrentChannel();
    $channel->update(['theme' => $theme]);
    core()->setCurrentChannel($channel->fresh());

    $homePage = Page::query()->where('slug', 'home')->firstOrFail();
    $heroAreaId = $homePage->template?->areas()->where('code', 'hero')->value('id')
        ?? $homePage->template?->areas()->orderBy('sort_order')->value('id');

    $sectionType = SectionType::query()->updateOrCreate(
        ['code' => 'hero'],
        [
            'name' => 'Hero',
            'category' => 'hero',
            'config_schema_json' => [],
            'supports_components' => false,
            'allowed_data_sources_json' => [],
            'renderer_class' => null,
            'is_active' => true,
        ]
    );

    $homePage->sections()->whereHas('sectionType', fn ($query) => $query->whereIn('code', ['hero', 'hero_slider', 'hero_banner']))->delete();

    PageSection::query()->create([
        'page_id' => $homePage->id,
        'template_area_id' => $heroAreaId,
        'section_type_id' => $sectionType->id,
        'sort_order' => 0,
        'title' => 'Homepage Hero',
        'settings_json' => [
            'mode' => 'static',
            'slides' => [
                [
                    'image' => 'storage/cms/homepage/hero/cms-inline-highlight.jpg',
                    'title' => 'Best Gadget Home Online',
                    'headline' => 'Best Gadget Home Online',
                    'highlight' => 'Gadget Home',
                    'body' => 'CMS hero body copy',
                    'badge' => 'CMS badge',
                    'primary_cta_label' => 'Shop CMS',
                    'primary_cta_url' => '/cms-sale',
                    'secondary_cta_label' => 'Learn more',
                    'secondary_cta_url' => '/cms-story',
                ],
            ],
        ],
        'is_active' => true,
    ]);

    $this->get(route('shop.home.index'))
        ->assertOk()
        ->assertSee('Best Gadget Home Online', false)
        ->assertSee('Gadget Home', false)
        ->assertDontSee('Best Gadget Home Online Gadget Home', false);
})->with([
    ['gadget'],
    ['clothing'],
]);

it('renders CMS header builder settings through active storefront themes', function (string $theme) {
    $channel = core()->getCurrentChannel();
    $channel->update(['theme' => $theme]);
    core()->setCurrentChannel($channel->fresh());

    $menu = Menu::query()->updateOrCreate(
        ['code' => 'cms-header-builder-test-'.$theme],
        ['name' => 'CMS Header Builder Test', 'location' => 'header', 'is_active' => true]
    );

    $menu->items()->delete();

    MenuItem::query()->create([
        'menu_id' => $menu->id,
        'title' => 'CMS Header Link',
        'type' => 'url',
        'target' => '/cms-header-link',
        'sort_order' => 1,
        'settings_json' => ['open_in_new_tab' => true],
        'is_active' => true,
    ]);

    HeaderConfig::query()->update(['is_default' => false]);
    HeaderConfig::query()->updateOrCreate(
        ['code' => 'cms_header_builder_render_test'],
        [
            'settings_json' => [
                'name' => 'CMS Header Builder Render Test',
                'logo_url' => 'https://example.test/cms-logo.svg',
                'announcement' => [
                    'enabled' => true,
                    'text' => 'CMS builder announcement',
                    'link' => 'https://example.test/announcement',
                ],
                'navigation' => [
                    'menu_id' => $menu->id,
                ],
                'features' => [
                    'show_search' => false,
                    'show_account' => false,
                    'show_cart' => false,
                    'sticky' => false,
                ],
                'variant' => 'classic',
            ],
            'is_default' => true,
        ]
    );

    $this->get(route('shop.home.index'))
        ->assertOk()
        ->assertSee('CMS builder announcement', false)
        ->assertSee('https://example.test/announcement', false)
        ->assertSee('https://example.test/cms-logo.svg', false)
        ->assertSee('CMS Header Link', false)
        ->assertSee('/cms-header-link', false)
        ->assertSee('target="_blank"', false)
        ->assertSee('gadget-header--static', false)
        ->assertDontSee('aria-label="Account"', false)
        ->assertDontSee('aria-label="Cart"', false)
        ->assertDontSee('name="query"', false);
})->with([
    ['gadget'],
    ['clothing'],
]);

it('renders CMS footer builder settings through active storefront themes', function (string $theme) {
    $channel = core()->getCurrentChannel();
    $channel->update(['theme' => $theme]);
    core()->setCurrentChannel($channel->fresh());

    $fallbackMenu = Menu::query()->updateOrCreate(
        ['code' => 'cms-footer-fallback-test-'.$theme],
        ['name' => 'Fallback Footer Menu', 'location' => 'footer', 'is_active' => true]
    );

    $companyMenu = Menu::query()->updateOrCreate(
        ['code' => 'cms-footer-company-test-'.$theme],
        ['name' => 'Selected Company Menu', 'location' => 'utility', 'is_active' => true]
    );

    $supportMenu = Menu::query()->updateOrCreate(
        ['code' => 'cms-footer-support-test-'.$theme],
        ['name' => 'Selected Support Menu', 'location' => 'header', 'is_active' => true]
    );

    $fallbackMenu->items()->delete();
    $companyMenu->items()->delete();
    $supportMenu->items()->delete();

    MenuItem::query()->create([
        'menu_id' => $fallbackMenu->id,
        'title' => 'Fallback Footer Link',
        'type' => 'url',
        'target' => '/fallback-footer-link',
        'sort_order' => 1,
        'settings_json' => [],
        'is_active' => true,
    ]);

    MenuItem::query()->create([
        'menu_id' => $companyMenu->id,
        'title' => 'Selected Company Link',
        'type' => 'url',
        'target' => '/selected-company-link',
        'sort_order' => 1,
        'settings_json' => ['open_in_new_tab' => true],
        'is_active' => true,
    ]);

    MenuItem::query()->create([
        'menu_id' => $supportMenu->id,
        'title' => 'Selected Support Link',
        'type' => 'url',
        'target' => '/selected-support-link',
        'sort_order' => 1,
        'settings_json' => [],
        'is_active' => true,
    ]);

    FooterConfig::query()->update(['is_default' => false]);
    FooterConfig::query()->updateOrCreate(
        ['code' => 'cms_footer_builder_render_test'],
        [
            'settings_json' => [
                'name' => 'CMS Footer Builder Render Test',
                'logo_url' => 'https://example.test/cms-footer-logo.svg',
                'description' => 'CMS managed footer description.',
                'newsletter' => [
                    'enabled' => true,
                    'heading' => 'CMS footer newsletter',
                    'text' => 'CMS footer newsletter text',
                ],
                'contact' => [
                    'email' => 'footer@example.test',
                    'phone' => '+880 123 456',
                ],
                'social' => [
                    'facebook' => 'https://facebook.com/cms-footer',
                    'instagram' => 'https://instagram.com/cms-footer',
                ],
                'navigation' => [
                    'menu_id' => $companyMenu->id,
                    'columns' => [
                        [
                            'title' => 'Company',
                            'menu_id' => $companyMenu->id,
                            'enabled' => true,
                            'sort_order' => 1,
                        ],
                        [
                            'title' => 'Support',
                            'menu_id' => $supportMenu->id,
                            'enabled' => true,
                            'sort_order' => 2,
                        ],
                    ],
                ],
                'copyright_text' => 'CMS footer copyright.',
                'variant' => 'simple',
            ],
            'is_default' => true,
        ]
    );

    $this->get(route('shop.home.index'))
        ->assertOk()
        ->assertSee('https://example.test/cms-footer-logo.svg', false)
        ->assertSee('CMS managed footer description.', false)
        ->assertSee('CMS footer newsletter', false)
        ->assertSee('CMS footer newsletter text', false)
        ->assertSee(route('shop.subscription.store'), false)
        ->assertSee('name="email"', false)
        ->assertSee('footer@example.test', false)
        ->assertSee('+880 123 456', false)
        ->assertSee('https://facebook.com/cms-footer', false)
        ->assertSee('https://instagram.com/cms-footer', false)
        ->assertSee('gadget-footer__social-icon', false)
        ->assertSee('Company', false)
        ->assertSee('Selected Company Link', false)
        ->assertSee('/selected-company-link', false)
        ->assertSee('Support', false)
        ->assertSee('Selected Support Link', false)
        ->assertSee('/selected-support-link', false)
        ->assertSee('target="_blank"', false)
        ->assertDontSee('Fallback Footer Link', false)
        ->assertDontSee('/fallback-footer-link', false)
        ->assertSee('CMS footer copyright.', false);
})->with([
    ['gadget'],
    ['clothing'],
]);

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
