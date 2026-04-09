<?php

namespace Platform\ExperienceCms\SectionTypes;

class ProductGallerySectionType extends AbstractSectionType
{
    public function code(): string
    {
        return 'product_gallery';
    }

    public function label(): string
    {
        return 'Product Gallery';
    }

    public function category(): string
    {
        return 'product_primary';
    }

    public function configSchema(): array
    {
        return ['headline' => 'string'];
    }

    public function defaultConfig(): array
    {
        return ['headline' => 'Gallery'];
    }

    public function validationRules(): array
    {
        return [];
    }

    public function rendererView(): string
    {
        return 'theme-default::storefront.sections.product-gallery';
    }
}
