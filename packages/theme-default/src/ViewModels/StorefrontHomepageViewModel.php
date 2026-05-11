<?php

namespace Platform\ThemeDefault\ViewModels;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Platform\ExperienceCms\Models\Page;
use Throwable;
use Webkul\Category\Models\Category;
use Webkul\Product\Models\ProductFlat;

class StorefrontHomepageViewModel
{
    public function build(): array
    {
        $saleProducts = $this->products('sale', 4);

        return [
            'saleProducts' => $saleProducts->isNotEmpty()
                ? $saleProducts
                : $this->products('featured', 4),
            'latestProducts' => $this->products('latest', 4),
            'categories' => $this->categories(4),
            'heroSliderImages' => $this->heroSliderImages(),
        ];
    }

    protected function heroSliderImages(): array
    {
        try {
            $page = Page::query()
                ->where('slug', 'home')
                ->where('status', Page::STATUS_PUBLISHED)
                ->first();

            if (! $page) {
                return [];
            }

            $section = $page->sections()
                ->where('is_active', true)
                ->whereHas('sectionType', fn ($query) => $query->where('code', 'hero_slider'))
                ->orderBy('sort_order')
                ->first();

            return collect($section?->settings_json['slides'] ?? [])
                ->filter(fn ($slide): bool => ! empty($slide['image']))
                ->map(fn ($slide): array => [
                    'image' => $slide['image'],
                    'link' => $slide['link'] ?? null,
                    'title' => $slide['title'] ?? 'Hero slide',
                ])
                ->values()
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    protected function products(string $mode, int $limit): Collection
    {
        $query = ProductFlat::query()
            ->with(['product.images', 'product.parent.images'])
            ->where('channel', core()->getRequestedChannelCode())
            ->where('locale', core()->getRequestedLocaleCode())
            ->where('status', 1)
            ->where('visible_individually', 1);

        match ($mode) {
            'sale' => $query
                ->whereNotNull('special_price')
                ->where('special_price', '>', 0)
                ->orderByDesc('product_id'),
            'featured' => $query
                ->where('featured', 1)
                ->orderByDesc('product_id'),
            default => $query->orderByDesc('product_id'),
        };

        return $query
            ->limit($limit)
            ->get()
            ->map(fn (ProductFlat $product) => $this->normalizeProduct($product))
            ->values();
    }

    protected function categories(int $limit): Collection
    {
        return Category::query()
            ->where('status', 1)
            ->whereNotNull('parent_id')
            ->orderBy('position')
            ->limit($limit)
            ->get()
            ->map(fn (Category $category) => $this->normalizeCategory($category))
            ->values();
    }

    protected function normalizeProduct(ProductFlat $product): array
    {
        $prices = $this->prices($product);
        $image = $this->productImage($product);

        return [
            'id' => $product->product_id,
            'name' => $product->name ?: $product->sku,
            'short_name' => Str::limit($product->name ?: $product->sku, 58),
            'sku' => $product->sku,
            'url' => $product->url_key ? url($product->url_key) : '#',
            'image' => $image,
            'regular_price' => $prices['regular'],
            'final_price' => $prices['final'],
            'has_discount' => $prices['has_discount'],
            'badge' => $prices['has_discount'] ? 'Sale' : ((bool) $product->new ? 'New' : null),
            'is_saleable' => $this->isSaleable($product),
        ];
    }

    protected function normalizeCategory(Category $category): array
    {
        $imagePath = $category->logo_path ?: $category->banner_path;

        return [
            'id' => $category->id,
            'name' => $category->name ?: 'Category',
            'url' => $category->slug ? url($category->slug) : '#',
            'image' => $imagePath ? url('cache/medium/'.$imagePath) : null,
        ];
    }

    protected function prices(ProductFlat $product): array
    {
        $regular = $this->formatPrice((float) $product->price);
        $final = $regular;
        $hasDiscount = false;

        try {
            $type = $product->product?->getTypeInstance();

            if ($type) {
                $productPrices = $type->getProductPrices();

                $regular = data_get($productPrices, 'regular.formatted_price')
                    ?? data_get($productPrices, 'from.regular.formatted_price')
                    ?? $regular;

                $final = data_get($productPrices, 'final.formatted_price')
                    ?? data_get($productPrices, 'from.final.formatted_price')
                    ?? $final;

                $hasDiscount = method_exists($type, 'haveDiscount') && $type->haveDiscount();
            }
        } catch (Throwable) {
            $hasDiscount = false;
        }

        if (
            ! $hasDiscount
            && $product->special_price !== null
            && (float) $product->special_price > 0
            && (float) $product->special_price < (float) $product->price
        ) {
            $final = $this->formatPrice((float) $product->special_price);
            $hasDiscount = true;
        }

        return [
            'regular' => $regular,
            'final' => $final,
            'has_discount' => $hasDiscount,
        ];
    }

    protected function productImage(ProductFlat $product): string
    {
        try {
            $image = product_image()->getProductBaseImage($product->product);

            return $image['medium_image_url']
                ?? $image['large_image_url']
                ?? $image['original_image_url']
                ?? bagisto_asset('images/medium-product-placeholder.webp', 'shop');
        } catch (Throwable) {
            return bagisto_asset('images/medium-product-placeholder.webp', 'shop');
        }
    }

    protected function isSaleable(ProductFlat $product): bool
    {
        try {
            return (bool) $product->product?->getTypeInstance()->isSaleable();
        } catch (Throwable) {
            return false;
        }
    }

    protected function formatPrice(float $price): string
    {
        return core()->currency($price);
    }
}
