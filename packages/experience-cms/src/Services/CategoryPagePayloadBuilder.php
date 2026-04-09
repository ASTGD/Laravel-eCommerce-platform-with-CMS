<?php

namespace Platform\ExperienceCms\Services;

use Illuminate\Support\Arr;
use Platform\ExperienceCms\Contracts\CategoryPagePayloadBuilderContract;
use Platform\ExperienceCms\Contracts\SiteSettingsResolverContract;
use Platform\ExperienceCms\Models\Page;
use Webkul\Category\Models\Category;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Shop\Http\Resources\ProductResource;

class CategoryPagePayloadBuilder implements CategoryPagePayloadBuilderContract
{
    public function __construct(
        protected StructuredPagePayloadBuilder $pages,
        protected ProductRepository $products,
        protected SiteSettingsResolverContract $siteSettings,
    ) {}

    public function build(Page $page, Category $category, array $context = []): array
    {
        $payload = $this->pages->build($page, ['category' => $category] + $context);
        $query = $context['query'] ?? request()->query();
        $listingDefaults = array_replace_recursive(
            [
                'default_mode' => 'grid',
                'limit' => 12,
                'show_toolbar' => true,
                'show_description' => true,
                'empty_state_heading' => 'No products matched this category yet.',
            ],
            $this->siteSettings->value('store.category_page'),
            Arr::get($page->settings_json ?? [], 'listing', [])
        );

        $searchEngine = core()->getConfigData('catalog.products.search.engine') === 'elastic'
            ? core()->getConfigData('catalog.products.search.storefront_mode')
            : 'database';

        $result = $this->products
            ->setSearchEngine($searchEngine ?: 'database')
            ->getAll(array_filter([
                'category_id' => $category->getKey(),
                'sort' => $query['sort'] ?? null,
                'limit' => $query['limit'] ?? $listingDefaults['limit'],
                'page' => $query['page'] ?? null,
            ], fn ($value) => $value !== null && $value !== ''));

        $sectionsByArea = collect($payload['sections'])->groupBy(fn (array $section) => $section['area'] ?: 'content');

        $products = collect($result->items())
            ->map(fn ($product) => ProductResource::make($product)->resolve(request()))
            ->values();

        return $payload + [
            'category' => $category,
            'heroSections' => $sectionsByArea->get('hero', collect())->values(),
            'preListingSections' => $sectionsByArea->get('pre_listing', collect())->values(),
            'postListingSections' => $sectionsByArea->get('post_listing', collect())->values(),
            'listing' => [
                'items' => $products,
                'paginator' => $result->appends($query),
                'settings' => $listingDefaults,
                'query' => $query,
                'mode' => $query['mode'] ?? $listingDefaults['default_mode'],
            ],
        ];
    }
}
