<?php

namespace Platform\ExperienceCms\Services;

use Platform\CommerceCore\Contracts\DataSourceResolverContract;
use Platform\ExperienceCms\Contracts\CategoryPagePayloadBuilderContract;
use Platform\ExperienceCms\Contracts\FooterResolverContract;
use Platform\ExperienceCms\Contracts\HeaderResolverContract;
use Platform\ExperienceCms\Contracts\MenuResolverContract;
use Platform\ExperienceCms\Contracts\PagePreviewServiceContract;
use Platform\ExperienceCms\Contracts\ProductPagePayloadBuilderContract;
use Platform\ExperienceCms\Contracts\SiteSettingsResolverContract;
use Platform\ExperienceCms\Models\Page;
use Platform\ThemeCore\Contracts\ComponentRendererContract;
use Platform\ThemeCore\Contracts\SectionRendererContract;
use Platform\ThemeCore\Contracts\ThemePresetResolverContract;

class PagePreviewService implements PagePreviewServiceContract
{
    public function __construct(
        protected StructuredPagePayloadBuilder $pages,
        protected CategoryPagePayloadBuilderContract $categoryPages,
        protected ProductPagePayloadBuilderContract $productPages,
    ) {}

    public function build(Page $page, array $context = []): array
    {
        return match ($page->type) {
            'category_page' => isset($context['category'])
                ? $this->categoryPages->build($page, $context['category'], $context)
                : $this->pages->build($page, $context),
            'product_page' => isset($context['product'])
                ? $this->productPages->build($page, $context['product'], $context)
                : $this->pages->build($page, $context),
            default => $this->pages->build($page, $context),
        };
    }
}
