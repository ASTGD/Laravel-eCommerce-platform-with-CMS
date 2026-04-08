<?php

namespace Platform\CommerceCore\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Platform\CommerceCore\Contracts\DataSourceResolverContract;
use Platform\CommerceCore\Enums\DataSourceType;
use Webkul\Category\Models\Category;
use Webkul\Product\Models\Product;

class DataSourceResolver implements DataSourceResolverContract
{
    public function resolve(string $sourceType, array $payload = [], array $context = []): Collection
    {
        return match ($sourceType) {
            DataSourceType::ManualProducts->value => $this->manualProducts($payload),
            DataSourceType::CategoryProducts->value => $this->categoryProducts($payload),
            DataSourceType::ManualCategories->value => $this->manualCategories($payload),
            DataSourceType::FeaturedProducts->value,
            DataSourceType::NewArrivals->value,
            DataSourceType::BestSellers->value,
            DataSourceType::DiscountedProducts->value => $this->latestProducts($payload),
            default => collect(),
        };
    }

    protected function manualProducts(array $payload): Collection
    {
        $ids = collect($payload['product_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Product::query()
            ->whereIn('id', $ids->all())
            ->get()
            ->sortBy(fn (Product $product) => $ids->search($product->id))
            ->values();
    }

    protected function categoryProducts(array $payload): Collection
    {
        $categoryId = (int) ($payload['category_id'] ?? 0);
        $limit = max(1, min(24, (int) ($payload['limit'] ?? 8)));

        if (! $categoryId) {
            return collect();
        }

        return Product::query()
            ->whereHas('categories', fn (Builder $query) => $query->where('id', $categoryId))
            ->limit($limit)
            ->get();
    }

    protected function manualCategories(array $payload): Collection
    {
        $ids = collect($payload['category_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Category::query()
            ->whereIn('id', $ids->all())
            ->get()
            ->sortBy(fn (Category $category) => $ids->search($category->id))
            ->values();
    }

    protected function latestProducts(array $payload): Collection
    {
        $limit = max(1, min(24, (int) ($payload['limit'] ?? 8)));

        return Product::query()
            ->latest('id')
            ->limit($limit)
            ->get();
    }
}
