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
        $saleProducts = $this->products('sale', 20);

        $hero = $this->heroSection();

        return [
            'saleProducts' => $saleProducts,
            'latestProducts' => $this->products('latest', 4),
            'featuredPicks' => $this->products('featured_picks', 12),
            'personalizedPicks' => $this->products('personalized_picks', 12),
            'categories' => $this->categories(24),
            'hero' => $hero,
            'heroSliderImages' => $this->heroSliderImages($hero),
        ];
    }

    protected function heroSection(): ?array
    {
        try {
            $page = Page::query()
                ->where('slug', 'home')
                ->where('status', Page::STATUS_PUBLISHED)
                ->first();

            if (! $page) {
                return null;
            }

            $section = $page->sections()
                ->where('is_active', true)
                ->whereHas('sectionType', fn ($query) => $query->whereIn('code', ['hero', 'hero_slider', 'hero_banner']))
                ->with('sectionType')
                ->orderBy('sort_order')
                ->first();

            if (! $section) {
                return null;
            }

            $settings = is_array($section->settings_json) ? $section->settings_json : [];

            return $this->normalizeHeroSection((string) $section->sectionType?->code, $settings);
        } catch (Throwable) {
            return null;
        }
    }

    protected function heroSliderImages(?array $hero = null): array
    {
        if (! $hero) {
            return [];
        }

        return collect($hero['slides'] ?? [])
            ->filter(fn ($slide): bool => ! empty($slide['image']))
            ->map(fn ($slide): array => [
                'image' => $slide['image'],
                'link' => $slide['primary_cta_url'] ?? null,
                'title' => $slide['title'] ?? $slide['headline'] ?? 'Hero slide',
            ])
            ->values()
            ->all();
    }

    protected function normalizeHeroSection(string $sectionCode, array $settings): ?array
    {
        $hero = match ($sectionCode) {
            'hero' => [
                'mode' => in_array($settings['mode'] ?? 'static', ['static', 'slider'], true) ? $settings['mode'] : 'static',
                'slides' => $settings['slides'] ?? [],
            ],
            'hero_slider' => [
                'mode' => 'slider',
                'slides' => $settings['slides'] ?? [],
            ],
            'hero_banner' => [
                'mode' => 'static',
                'slides' => [[
                    'image' => $settings['image'] ?? '',
                    'title' => $settings['title'] ?? $settings['headline'] ?? '',
                    'headline' => $settings['headline'] ?? '',
                    'body' => $settings['body'] ?? '',
                    'primary_cta_label' => $settings['primary_cta_label'] ?? '',
                    'primary_cta_url' => $settings['primary_cta_url'] ?? '',
                    'secondary_cta_label' => $settings['secondary_cta_label'] ?? '',
                    'secondary_cta_url' => $settings['secondary_cta_url'] ?? '',
                ]],
            ],
            default => null,
        };

        if (! $hero) {
            return null;
        }

        $slides = collect($hero['slides'] ?? [])
            ->take(5)
            ->map(fn (array $slide): array => [
                'image' => trim((string) ($slide['image'] ?? '')),
                'title' => trim((string) ($slide['title'] ?? '')),
                'headline' => trim((string) ($slide['headline'] ?? $slide['title'] ?? '')),
                'body' => trim((string) ($slide['body'] ?? '')),
                'primary_cta_label' => trim((string) ($slide['primary_cta_label'] ?? '')),
                'primary_cta_url' => trim((string) ($slide['primary_cta_url'] ?? $slide['link'] ?? '')),
                'secondary_cta_label' => trim((string) ($slide['secondary_cta_label'] ?? '')),
                'secondary_cta_url' => trim((string) ($slide['secondary_cta_url'] ?? '')),
            ])
            ->filter(fn (array $slide): bool => collect($slide)->filter(fn ($value): bool => filled($value))->isNotEmpty())
            ->values();

        if ($slides->isEmpty()) {
            return null;
        }

        return [
            'mode' => $hero['mode'] === 'slider' && $slides->count() > 1 ? 'slider' : 'static',
            'slides' => $slides->all(),
        ];
    }

    protected function products(string $mode, int $limit): Collection
    {
        $query = ProductFlat::query()
            ->with(['product.images', 'product.parent.images'])
            ->where('channel', core()->getRequestedChannelCode())
            ->where('locale', core()->getRequestedLocaleCode())
            ->where('status', 1)
            ->where('visible_individually', 1);

        $query = match ($mode) {
            'sale' => $query
                ->whereHas('product.categories.translations', function ($q) {
                    $q->where('slug', 'limited-sale');
                })
                ->orderByDesc('product_id'),
            'featured_picks' => $query
                ->whereHas('product.categories', function ($q) {
                    $q->whereHas('translations', function ($q2) {
                        $q2->where('slug', 'featured-picks');
                    });
                })
                ->orderByDesc('product_id'),
            'latest' => $query
                ->whereHas('product.categories', function ($q) {
                    $q->whereHas('translations', function ($q2) {
                        $q2->where('slug', 'new-arrivals');
                    });
                })
                ->orderByDesc('product_id'),
            'personalized_picks' => $query
                ->whereHas('product.categories', function ($q) {
                    $q->whereHas('translations', function ($q2) {
                        $q2->where('slug', 'personalized-picks');
                    });
                })
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
            ->withCount('products')
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
            'count' => $category->products_count ?? 0,
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
