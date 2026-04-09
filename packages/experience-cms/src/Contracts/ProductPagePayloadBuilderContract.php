<?php

namespace Platform\ExperienceCms\Contracts;

use Platform\ExperienceCms\Models\Page;
use Webkul\Product\Contracts\Product;

interface ProductPagePayloadBuilderContract
{
    public function build(Page $page, Product $product, array $context = []): array;
}
