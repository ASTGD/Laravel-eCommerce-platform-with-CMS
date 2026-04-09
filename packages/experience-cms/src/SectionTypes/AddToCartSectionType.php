<?php

namespace Platform\ExperienceCms\SectionTypes;

class AddToCartSectionType extends AbstractSectionType
{
    public function code(): string
    {
        return 'add_to_cart';
    }

    public function label(): string
    {
        return 'Add To Cart';
    }

    public function category(): string
    {
        return 'product_purchase';
    }

    public function configSchema(): array
    {
        return ['default_quantity' => 'integer', 'button_label' => 'string'];
    }

    public function defaultConfig(): array
    {
        return ['default_quantity' => 1, 'button_label' => 'Add to cart'];
    }

    public function validationRules(): array
    {
        return [
            'default_quantity' => ['nullable', 'integer', 'min:1', 'max:10'],
        ];
    }

    public function rendererView(): string
    {
        return 'theme-default::storefront.sections.product-add-to-cart';
    }
}
