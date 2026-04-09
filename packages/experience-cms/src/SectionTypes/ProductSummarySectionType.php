<?php

namespace Platform\ExperienceCms\SectionTypes;

class ProductSummarySectionType extends AbstractSectionType
{
    public function code(): string
    {
        return 'product_summary';
    }

    public function label(): string
    {
        return 'Product Summary';
    }

    public function category(): string
    {
        return 'product_primary';
    }

    public function configSchema(): array
    {
        return ['show_sku' => 'boolean'];
    }

    public function defaultConfig(): array
    {
        return ['show_sku' => true];
    }

    public function validationRules(): array
    {
        return [];
    }

    public function rendererView(): string
    {
        return 'theme-default::storefront.sections.product-summary';
    }
}
