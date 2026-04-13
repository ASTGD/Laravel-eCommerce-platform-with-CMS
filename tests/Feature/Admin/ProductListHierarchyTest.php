<?php

use Webkul\Admin\Tests\AdminTestCase;
use Webkul\Faker\Helpers\Product as ProductFaker;

use function Pest\Laravel\get;

uses(AdminTestCase::class);

it('shows only parent products in the product list datagrid and nests variants under them', function () {
    $product = (new ProductFaker)->getConfigurableProductFactory()->create();

    $this->loginAsAdmin();

    $response = get(route('admin.catalog.products.index'), [
        'X-Requested-With' => 'XMLHttpRequest',
    ])->assertOk();

    $records = collect($response->json('records'));
    $parentRecord = $records->firstWhere('product_id', $product->id);

    expect($parentRecord)->not->toBeNull()
        ->and($records->pluck('product_id')->all())->not->toContain($product->variants()->first()->id)
        ->and($parentRecord['has_variants'])->toBeTrue()
        ->and($parentRecord['variant_count'])->toBe($product->variants()->count())
        ->and(collect($parentRecord['variants'])->pluck('product_id')->sort()->values()->all())
            ->toBe($product->variants()->pluck('id')->sort()->values()->all());
});

it('returns the parent product when searching by a variant sku', function () {
    $product = (new ProductFaker)->getConfigurableProductFactory()->create();
    $variant = $product->variants()->firstOrFail();

    $this->loginAsAdmin();

    $response = get(
        route('admin.catalog.products.index', [
            'filters' => [
                'all' => [$variant->sku],
            ],
            'pagination' => [
                'page' => 1,
                'per_page' => 10,
            ],
        ]),
        ['X-Requested-With' => 'XMLHttpRequest']
    )->assertOk();

    $records = collect($response->json('records'));

    expect($records->pluck('product_id')->all())
        ->toContain($product->id)
        ->not->toContain($variant->id);
});
