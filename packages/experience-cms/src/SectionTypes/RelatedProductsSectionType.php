<?php

namespace Platform\ExperienceCms\SectionTypes;

class RelatedProductsSectionType extends AbstractSectionType
{
    public function code(): string
    {
        return 'related_products';
    }

    public function label(): string
    {
        return 'Related Products';
    }

    public function category(): string
    {
        return 'product_related';
    }

    public function configSchema(): array
    {
        return ['headline' => 'string', 'limit' => 'integer', 'mode' => 'string'];
    }

    public function defaultConfig(): array
    {
        return ['headline' => 'You may also like', 'limit' => 4, 'mode' => 'related'];
    }

    public function validationRules(): array
    {
        return [
            'limit' => ['integer', 'min:1', 'max:12'],
            'mode' => ['required', 'in:related,up_sell'],
        ];
    }

    public function rendererView(): string
    {
        return 'theme-default::storefront.sections.related-products';
    }
}
