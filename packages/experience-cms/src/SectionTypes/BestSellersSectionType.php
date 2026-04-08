<?php

namespace Platform\ExperienceCms\SectionTypes;

class BestSellersSectionType extends AbstractSectionType
{
    public function code(): string
    {
        return 'best_sellers';
    }

    public function label(): string
    {
        return 'Best Sellers';
    }

    public function category(): string
    {
        return 'catalog';
    }

    public function configSchema(): array
    {
        return ['limit' => 'integer'];
    }

    public function defaultConfig(): array
    {
        return ['limit' => 8];
    }

    public function validationRules(): array
    {
        return ['limit' => ['integer', 'min:1', 'max:24']];
    }

    public function allowedDataSources(): array
    {
        return ['best_sellers'];
    }

    public function rendererView(): string
    {
        return 'theme-default::storefront.sections.featured-products';
    }
}
