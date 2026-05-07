<?php

use Platform\ThemeCore\Models\ThemePreset;
use Webkul\Admin\Tests\AdminTestCase;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Faker\Helpers\Product as ProductFaker;

uses(AdminTestCase::class);

beforeEach(function () {
    $this->withoutVite();
});

it('activates Gadget and Default presets through the theme admin flow', function () {
    $this->loginAsAdmin();

    $channel = core()->getCurrentChannel();
    $channel->update(['theme' => config('themes.shop-default')]);
    core()->setCurrentChannel($channel->fresh());

    $native = themeActivationPreset('default', 'Default', 'default', true);
    $gadget = themeActivationPreset('gadget', 'Gadget', 'gadget', false);

    $this->post(route('admin.theme.presets.set-active', $gadget->id))
        ->assertRedirect();

    expect($channel->fresh()->theme)->toBe('gadget')
        ->and($gadget->fresh()->is_active)->toBeTrue()
        ->and($native->fresh()->is_active)->toBeFalse();

    $this->get(route('shop.home.index'))
        ->assertOk()
        ->assertSee('gadget-home', false)
        ->assertSeeText('Ensuring Happiness At Every Step');

    $this->post(route('admin.theme.presets.set-active', $native->id))
        ->assertRedirect();

    expect($channel->fresh()->theme)->toBe('default')
        ->and($native->fresh()->is_active)->toBeTrue()
        ->and($gadget->fresh()->is_active)->toBeFalse();

    $this->get(route('shop.home.index'))
        ->assertOk()
        ->assertDontSee('gadget-home', false);
});

it('falls back to native category and product views when Gadget has no override', function () {
    $channel = core()->getCurrentChannel();
    $channel->update(['theme' => 'gadget']);
    core()->setCurrentChannel($channel->fresh());

    $category = app(CategoryRepository::class)->create([
        'locale' => 'all',
        'name' => 'Gadget Native Fallback',
        'slug' => 'gadget-native-fallback',
        'description' => 'Native category fallback under Gadget theme.',
        'meta_title' => 'Gadget Native Fallback',
        'meta_description' => 'Native category fallback under Gadget theme.',
        'position' => 1,
        'status' => 1,
        'display_mode' => 'products_and_description',
        'parent_id' => 1,
        'attributes' => [],
    ]);

    $product = (new ProductFaker)->getSimpleProductFactory()->create([
        'sku' => 'gadget-native-fallback-product',
    ]);

    $product->categories()->syncWithoutDetaching([$category->id]);

    $this->get(route('shop.product_or_category.index', $category->slug))
        ->assertOk()
        ->assertSee('<v-category>', false)
        ->assertDontSee('gadget-home', false);

    $this->get(route('shop.product_or_category.index', $product->url_key))
        ->assertOk()
        ->assertSeeText($product->name)
        ->assertDontSee('gadget-home', false);
});

function themeActivationPreset(string $code, string $name, string $shopThemeCode, bool $active): ThemePreset
{
    return ThemePreset::query()->updateOrCreate(
        ['code' => $code],
        [
            'name' => $name,
            'tokens_json' => [
                'code' => $code,
                'name' => $name,
            ],
            'settings_json' => [
                'shop_theme_code' => $shopThemeCode,
            ],
            'is_default' => $shopThemeCode === config('themes.shop-default'),
            'is_active' => $active,
        ]
    );
}
