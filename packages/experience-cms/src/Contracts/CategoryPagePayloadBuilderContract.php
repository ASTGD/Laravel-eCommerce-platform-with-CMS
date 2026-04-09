<?php

namespace Platform\ExperienceCms\Contracts;

use Platform\ExperienceCms\Models\Page;
use Webkul\Category\Models\Category;

interface CategoryPagePayloadBuilderContract
{
    public function build(Page $page, Category $category, array $context = []): array;
}
