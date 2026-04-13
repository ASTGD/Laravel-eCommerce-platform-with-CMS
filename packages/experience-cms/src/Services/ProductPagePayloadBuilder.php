<?php

namespace Platform\ExperienceCms\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Platform\ExperienceCms\Contracts\ProductPagePayloadBuilderContract;
use Platform\ExperienceCms\Contracts\SiteSettingsResolverContract;
use Platform\ExperienceCms\Models\Page;
use Webkul\Product\Helpers\ConfigurableOption;
use Webkul\Product\Helpers\ProductType;
use Webkul\Product\Contracts\Product;
use Webkul\Product\Helpers\View as ProductViewHelper;
use Webkul\Shop\Http\Resources\ProductResource;

class ProductPagePayloadBuilder implements ProductPagePayloadBuilderContract
{
    public function __construct(
        protected StructuredPagePayloadBuilder $pages,
        protected ProductViewHelper $productViewHelper,
        protected ConfigurableOption $configurableOption,
        protected SiteSettingsResolverContract $siteSettings,
    ) {}

    public function build(Page $page, Product $product, array $context = []): array
    {
        $product->loadMissing(['images', 'super_attributes.options', 'variants.images', 'customizable_options']);

        $relatedLimit = (int) Arr::get($page->settings_json ?? [], 'related.limit', 4);
        $gallery = $this->buildGallery($product);
        $baseImage = $gallery[0] ?? product_image()->getProductBaseImage($product);
        $configurableConfig = ProductType::hasVariants($product->type)
            ? $this->configurableOption->getConfigurationConfig($product)
            : [];

        $productData = [
            'resource' => ProductResource::make($product)->resolve(request()),
            'gallery' => $gallery,
            'base_image' => $baseImage,
            'price_html' => $product->getTypeInstance()->getPriceHtml(),
            'details' => collect($this->productViewHelper->getAdditionalData($product))
                ->filter(fn (array $item) => filled($item['value']))
                ->values(),
            'super_attributes' => $product->super_attributes()->get(['attributes.id', 'attributes.code', 'attributes.admin_name']),
            'customizable_options' => $product->customizable_options()->get(),
            'configurable_config' => $configurableConfig,
            'shipping_note' => Arr::get(
                $page->settings_json ?? [],
                'shipping_note',
                Arr::get($this->siteSettings->value('store.product_page'), 'shipping_note', 'Shipping guidance is configured in site settings.')
            ),
            'related_products' => $product->related_products()->with(['images', 'price_indices', 'reviews'])->limit($relatedLimit)->get()
                ->map(fn ($relatedProduct) => ProductResource::make($relatedProduct)->resolve(request()))
                ->values(),
            'up_sell_products' => $product->up_sells()->with(['images', 'price_indices', 'reviews'])->limit($relatedLimit)->get()
                ->map(fn ($upSellProduct) => ProductResource::make($upSellProduct)->resolve(request()))
                ->values(),
        ];

        $payload = $this->pages->build($page, ['product' => $product, 'productData' => $productData] + $context);
        $sectionsByArea = collect($payload['sections'])->groupBy(fn (array $section) => $section['area'] ?: 'content');

        return $payload + [
            'product' => $product,
            'productData' => $productData,
            'gallerySections' => $sectionsByArea->get('gallery', collect())->values(),
            'summarySections' => $sectionsByArea->get('summary', collect())->values(),
            'detailsSections' => $sectionsByArea->get('details', collect())->values(),
            'relatedSections' => $sectionsByArea->get('related', collect())->values(),
        ];
    }

    /**
     * Build a storage-backed gallery payload for the storefront.
     */
    protected function buildGallery(Product $product): array
    {
        $gallery = collect($product->images)
            ->map(function ($image) {
                if (! Storage::has($image->path)) {
                    return null;
                }

                $url = Storage::url($image->path);

                return [
                    'small_image_url' => $url,
                    'medium_image_url' => $url,
                    'large_image_url' => $url,
                    'original_image_url' => $url,
                    'path' => $image->path,
                ];
            })
            ->filter()
            ->values()
            ->all();

        if (! empty($gallery)) {
            return $gallery;
        }

        return product_image()->getGalleryImages($product);
    }
}
