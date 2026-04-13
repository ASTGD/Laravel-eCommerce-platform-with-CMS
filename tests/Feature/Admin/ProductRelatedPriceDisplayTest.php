<?php

use Webkul\Admin\Http\Resources\ProductResource;
use Webkul\Admin\Tests\AdminTestCase;
use Webkul\Product\Models\Product;

uses(AdminTestCase::class);

it('shows the lowest variant price for configurable related products', function () {
    $relatedProduct = Product::query()
        ->where('sku', 'ASTGD-SHIRT-RF')
        ->with('variants')
        ->firstOrFail();

    $expectedPrice = core()->currency(
        $relatedProduct->variants->pluck('price')->filter()->min()
    );

    expect($relatedProduct->fresh()->formatted_price)->toBe($expectedPrice);

    $resource = (new ProductResource($relatedProduct->fresh()))->toArray(request());

    expect($resource['formatted_price'])->toBe($expectedPrice);
});
