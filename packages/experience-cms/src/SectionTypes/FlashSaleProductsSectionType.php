<?php

namespace Platform\ExperienceCms\SectionTypes;

class FlashSaleProductsSectionType extends AbstractSectionType
{
    public function code(): string
    {
        return 'flash_sale_products';
    }

    public function label(): string
    {
        return 'Flash Sale Products';
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
        return ['discounted_products'];
    }

    public function rendererView(): string
    {
        return 'theme-default::storefront.sections.featured-products';
    }
}
