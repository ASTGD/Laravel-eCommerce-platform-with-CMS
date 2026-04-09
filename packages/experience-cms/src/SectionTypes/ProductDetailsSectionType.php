<?php

namespace Platform\ExperienceCms\SectionTypes;

class ProductDetailsSectionType extends AbstractSectionType
{
    public function code(): string
    {
        return 'product_details';
    }

    public function label(): string
    {
        return 'Product Details';
    }

    public function category(): string
    {
        return 'product_details';
    }

    public function configSchema(): array
    {
        return ['headline' => 'string'];
    }

    public function defaultConfig(): array
    {
        return ['headline' => 'Product Details'];
    }

    public function validationRules(): array
    {
        return [
            'headline' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function rendererView(): string
    {
        return 'theme-default::storefront.sections.product-details';
    }
}
