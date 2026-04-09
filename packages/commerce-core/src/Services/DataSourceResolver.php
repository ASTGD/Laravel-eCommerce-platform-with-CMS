<?php

namespace Platform\CommerceCore\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Platform\CommerceCore\Contracts\DataSourceResolverContract;
use Platform\CommerceCore\Enums\DataSourceType;
use Webkul\Category\Models\Category;
use Webkul\Product\Models\ProductFlat;

class DataSourceResolver implements DataSourceResolverContract
{
    public function resolve(string $sourceType, array $payload = [], array $context = []): Collection
    {
        return match ($sourceType) {
            DataSourceType::ManualProducts->value => $this->manualProducts($payload),
            DataSourceType::CategoryProducts->value => $this->categoryProducts($payload),
            DataSourceType::ManualCategories->value => $this->manualCategories($payload),
            DataSourceType::FeaturedProducts->value => $this->flaggedProducts('featured', $payload),
            DataSourceType::NewArrivals->value => $this->flaggedProducts('new', $payload),
            DataSourceType::BestSellers->value => $this->bestSellers($payload),
            DataSourceType::DiscountedProducts->value => $this->discountedProducts($payload),
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

        return $this->baseProductQuery()
            ->whereIn('product_id', $ids->all())
            ->get()
            ->sortBy(fn (ProductFlat $product) => $ids->search($product->product_id))
            ->values();
    }

    protected function categoryProducts(array $payload): Collection
    {
        $categoryId = (int) ($payload['category_id'] ?? 0);
        $limit = max(1, min(24, (int) ($payload['limit'] ?? 8)));

        if (! $categoryId) {
            return collect();
        }

        return $this->baseProductQuery()
            ->whereExists(function ($query) use ($categoryId) {
                $query->selectRaw('1')
                    ->from('product_categories')
                    ->whereColumn('product_categories.product_id', 'product_flat.product_id')
                    ->where('product_categories.category_id', $categoryId);
            })
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

    protected function flaggedProducts(string $column, array $payload): Collection
    {
        $limit = max(1, min(24, (int) ($payload['limit'] ?? 8)));

        return $this->baseProductQuery()
            ->where($column, 1)
            ->orderByDesc('product_id')
            ->limit($limit)
            ->get();
    }

    protected function bestSellers(array $payload): Collection
    {
        $limit = max(1, min(24, (int) ($payload['limit'] ?? 8)));

        $productIds = DB::table('sales_order_items')
            ->select('product_id')
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->orderByRaw('COUNT(*) DESC')
            ->limit($limit)
            ->pluck('product_id');

        if ($productIds->isEmpty()) {
            return collect();
        }

        return $this->baseProductQuery()
            ->whereIn('product_id', $productIds->all())
            ->get()
            ->sortBy(fn (ProductFlat $product) => $productIds->search($product->product_id))
            ->values();
    }

    protected function discountedProducts(array $payload): Collection
    {
        $limit = max(1, min(24, (int) ($payload['limit'] ?? 8)));

        return $this->baseProductQuery()
            ->whereNotNull('special_price')
            ->where('special_price', '>', 0)
            ->orderByDesc('product_id')
            ->limit($limit)
            ->get();
    }

    protected function baseProductQuery(): Builder
    {
        $channelCode = core()->getRequestedChannelCode();
        $localeCode = core()->getRequestedLocaleCode();

        return ProductFlat::query()
            ->where('channel', $channelCode)
            ->where('locale', $localeCode)
            ->where('status', 1)
            ->where('visible_individually', 1);
    }
}
