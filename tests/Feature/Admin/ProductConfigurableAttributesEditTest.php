<?php

use Illuminate\Support\Facades\DB;
use Webkul\Admin\Tests\AdminTestCase;
use Webkul\Faker\Helpers\Product as ProductFaker;

use function Pest\Laravel\get;
use function Pest\Laravel\put;

uses(AdminTestCase::class);

it('shows the configurable attributes card on configurable product edit pages', function () {
    $product = (new ProductFaker)->getConfigurableProductFactory()->create();

    $this->loginAsAdmin();

    get(route('admin.catalog.products.edit', $product->id))
        ->assertOk()
        ->assertSeeText('Configurable Attributes')
        ->assertSeeText('Generate Variants')
        ->assertSeeText('Color')
        ->assertSeeText('Size');
});

it('updates configurable super attributes from the product edit page', function () {
    $product = (new ProductFaker)->getConfigurableProductFactory()->create();

    $attributeCode = 'storage_edit_'.str()->lower(str()->random(8));

    $storageAttributeId = DB::table('attributes')->insertGetId([
        'code' => $attributeCode,
        'admin_name' => 'Storage',
        'type' => 'select',
        'swatch_type' => '',
        'validation' => null,
        'position' => 1,
        'is_required' => 0,
        'is_unique' => 0,
        'is_filterable' => 0,
        'is_comparable' => 0,
        'is_configurable' => 1,
        'is_user_defined' => 1,
        'is_visible_on_front' => 1,
        'value_per_locale' => 0,
        'value_per_channel' => 0,
        'enable_wysiwyg' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $product->attribute_family
        ->attribute_groups()
        ->firstOrFail()
        ->custom_attributes()
        ->attach($storageAttributeId, ['position' => 999]);

    $variants = [];

    foreach ($product->variants()->with('inventories')->get() as $variant) {
        $variants[$variant->id] = [
            'sku' => $variant->sku,
            'name' => $variant->name,
            'price' => $variant->price,
            'weight' => $variant->weight,
            'status' => $variant->status,
            'color' => $variant->color,
            'size' => $variant->size,
            'inventories' => $variant->inventories->pluck('qty', 'inventory_source_id')->all(),
        ];
    }

    $originalVariantIds = array_keys($variants);

    $this->loginAsAdmin();

    put(route('admin.catalog.products.update', $product->id), [
        'sku' => $product->sku,
        'url_key' => $product->url_key,
        'channel' => core()->getCurrentChannelCode(),
        'locale' => app()->getLocale(),
        'short_description' => $product->short_description,
        'description' => $product->description,
        'name' => $product->name,
        'rma_rule_id' => 1,
        'super_attribute_codes' => ['color', $attributeCode],
        'variants' => $variants,
    ])->assertRedirect(route('admin.catalog.products.index'));

    $product->refresh();

    expect($product->super_attributes->pluck('code')->all())
        ->toContain('color')
        ->toContain($attributeCode)
        ->not->toContain('size');
});

it('saves multiple newly generated variants after changing configurable attributes', function () {
    $product = (new ProductFaker)->getConfigurableProductFactory()->create();

    $attributeCode = 'storage_multi_'.str()->lower(str()->random(8));

    $storageAttributeId = DB::table('attributes')->insertGetId([
        'code' => $attributeCode,
        'admin_name' => 'Storage',
        'type' => 'select',
        'swatch_type' => '',
        'validation' => null,
        'position' => 1,
        'is_required' => 0,
        'is_unique' => 0,
        'is_filterable' => 0,
        'is_comparable' => 0,
        'is_configurable' => 1,
        'is_user_defined' => 1,
        'is_visible_on_front' => 1,
        'value_per_locale' => 0,
        'value_per_channel' => 0,
        'enable_wysiwyg' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('attribute_options')->insert([
        [
            'attribute_id' => $storageAttributeId,
            'admin_name' => '128 GB',
            'sort_order' => 1,
            'swatch_value' => null,
        ],
        [
            'attribute_id' => $storageAttributeId,
            'admin_name' => '256 GB',
            'sort_order' => 2,
            'swatch_value' => null,
        ],
    ]);

    $product->attribute_family
        ->attribute_groups()
        ->firstOrFail()
        ->custom_attributes()
        ->attach($storageAttributeId, ['position' => 999]);

    $storageOptionIds = DB::table('attribute_options')
        ->where('attribute_id', $storageAttributeId)
        ->orderBy('sort_order')
        ->pluck('id')
        ->values();

    $variants = [];

    foreach ($product->variants()->with('inventories')->get() as $variant) {
        $variants[$variant->id] = [
            'sku' => $variant->sku,
            'name' => $variant->name,
            'price' => $variant->price,
            'weight' => $variant->weight,
            'status' => $variant->status,
            'color' => $variant->color,
            'size' => $variant->size,
            'inventories' => $variant->inventories->pluck('qty', 'inventory_source_id')->all(),
        ];
    }

    $originalVariantIds = array_keys($variants);

    $colorOptionIds = $product->super_attributes()
        ->where('code', 'color')
        ->with('options')
        ->firstOrFail()
        ->options
        ->pluck('id')
        ->values();

    $variants['variant_'.count($variants)] = [
        'sku' => $product->sku.'-BLACK-128',
        'name' => $product->name.' - Black / 128 GB',
        'price' => 999,
        'weight' => 1,
        'status' => 1,
        'color' => $colorOptionIds[0],
        $attributeCode => $storageOptionIds[0],
        'inventories' => [1 => 5],
    ];

    $variants['variant_'.(count($variants) + 1)] = [
        'sku' => $product->sku.'-BLUE-256',
        'name' => $product->name.' - Blue / 256 GB',
        'price' => 1099,
        'weight' => 1,
        'status' => 1,
        'color' => $colorOptionIds[1],
        $attributeCode => $storageOptionIds[1],
        'inventories' => [1 => 7],
    ];

    $this->loginAsAdmin();

    put(route('admin.catalog.products.update', $product->id), [
        'sku' => $product->sku,
        'url_key' => $product->url_key,
        'channel' => core()->getCurrentChannelCode(),
        'locale' => app()->getLocale(),
        'short_description' => $product->short_description,
        'description' => $product->description,
        'name' => $product->name,
        'rma_rule_id' => 1,
        'super_attribute_codes' => ['color', $attributeCode],
        'variants' => $variants,
    ])->assertRedirect(route('admin.catalog.products.index'));

    $newVariants = $product->fresh()
        ->variants()
        ->whereNotIn('id', $originalVariantIds)
        ->get();

    $savedStorageValueCount = DB::table('product_attribute_values')
        ->where('attribute_id', $storageAttributeId)
        ->whereIn('product_id', $newVariants->pluck('id'))
        ->count();

    expect($newVariants)->toHaveCount(2)
        ->and($savedStorageValueCount)->toBe(2);
});
