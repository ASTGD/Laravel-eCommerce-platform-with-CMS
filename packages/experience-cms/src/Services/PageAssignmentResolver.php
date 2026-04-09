<?php

namespace Platform\ExperienceCms\Services;

use Platform\ExperienceCms\Contracts\PageAssignmentResolverContract;
use Platform\ExperienceCms\Models\PageAssignment;
use Webkul\Category\Models\Category;
use Webkul\Product\Contracts\Product;

class PageAssignmentResolver implements PageAssignmentResolverContract
{
    public function resolve(string $pageType, array $context = []): ?PageAssignment
    {
        $entityType = $context['entity_type'] ?? null;
        $entityId = $context['entity_id'] ?? null;

        if ($entityType && $entityId) {
            $assignment = $this->baseQuery($pageType)
                ->where('scope_type', PageAssignment::SCOPE_ENTITY)
                ->where('entity_type', $entityType)
                ->where('entity_id', $entityId)
                ->orderByDesc('priority')
                ->orderBy('id')
                ->first();

            if ($assignment) {
                return $assignment;
            }
        }

        return $this->baseQuery($pageType)
            ->where('scope_type', PageAssignment::SCOPE_GLOBAL)
            ->orderByDesc('priority')
            ->orderBy('id')
            ->first();
    }

    public function resolveForCategory(Category $category): ?PageAssignment
    {
        return $this->resolve('category_page', [
            'entity_type' => PageAssignment::ENTITY_CATEGORY,
            'entity_id' => $category->getKey(),
        ]);
    }

    public function resolveForProduct(Product $product): ?PageAssignment
    {
        return $this->resolve('product_page', [
            'entity_type' => PageAssignment::ENTITY_PRODUCT,
            'entity_id' => $product->getKey(),
        ]);
    }

    protected function baseQuery(string $pageType)
    {
        return PageAssignment::query()
            ->with([
                'page.template.areas',
                'page.sections.sectionType',
                'page.sections.templateArea',
                'page.sections.components.componentType',
                'page.headerConfig',
                'page.footerConfig',
                'page.menu.items.children',
                'page.themePreset',
                'page.seoMeta',
            ])
            ->where('page_type', $pageType)
            ->where('is_active', true)
            ->whereHas('page', fn ($query) => $query->where('type', $pageType));
    }
}
