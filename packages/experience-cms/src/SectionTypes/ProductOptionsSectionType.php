<?php

namespace Platform\ExperienceCms\SectionTypes;

class ProductOptionsSectionType extends AbstractSectionType
{
    public function code(): string
    {
        return 'product_options';
    }

    public function label(): string
    {
        return 'Product Options';
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
        return ['headline' => 'Options'];
    }

    public function validationRules(): array
    {
        return [];
    }

    public function rendererView(): string
    {
        return 'theme-default::storefront.sections.product-options';
    }
}
