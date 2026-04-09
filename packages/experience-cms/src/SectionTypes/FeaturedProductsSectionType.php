<?php

namespace Platform\ExperienceCms\SectionTypes;

class FeaturedProductsSectionType extends AbstractSectionType
{
    public function code(): string
    {
        return 'featured_products';
    }

    public function label(): string
    {
        return 'Featured Products';
    }

    public function category(): string
    {
        return 'catalog';
    }

    public function configSchema(): array
    {
        return ['eyebrow' => 'string', 'limit' => 'integer'];
    }

    public function defaultConfig(): array
    {
        return ['eyebrow' => 'Featured Products', 'limit' => 8];
    }

    public function validationRules(): array
    {
        return ['limit' => ['integer', 'min:1', 'max:24']];
    }

    public function allowedDataSources(): array
    {
        return ['featured_products', 'manual_products', 'category_products'];
    }

    public function supportsComponents(): bool
    {
        return true;
    }

    public function rendererView(): string
    {
        return 'theme-default::storefront.sections.featured-products';
    }
}
