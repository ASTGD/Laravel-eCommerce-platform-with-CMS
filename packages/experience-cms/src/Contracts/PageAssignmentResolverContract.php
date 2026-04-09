<?php

namespace Platform\ExperienceCms\Contracts;

use Platform\ExperienceCms\Models\PageAssignment;
use Webkul\Category\Models\Category;
use Webkul\Product\Contracts\Product;

interface PageAssignmentResolverContract
{
    public function resolve(string $pageType, array $context = []): ?PageAssignment;

    public function resolveForCategory(Category $category): ?PageAssignment;

    public function resolveForProduct(Product $product): ?PageAssignment;
}
