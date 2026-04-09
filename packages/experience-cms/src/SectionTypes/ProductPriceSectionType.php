<?php

namespace Platform\ExperienceCms\SectionTypes;

class ProductPriceSectionType extends AbstractSectionType
{
    public function code(): string
    {
        return 'product_price';
    }

    public function label(): string
    {
        return 'Product Price';
    }

    public function category(): string
    {
        return 'product_purchase';
    }

    public function configSchema(): array
    {
        return ['headline' => 'string'];
    }

    public function defaultConfig(): array
    {
        return ['headline' => 'Price'];
    }

    public function validationRules(): array
    {
        return [];
    }

    public function rendererView(): string
    {
        return 'theme-default::storefront.sections.product-price';
    }
}
