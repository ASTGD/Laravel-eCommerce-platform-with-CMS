<?php

namespace Platform\ExperienceCms\SectionTypes;

class StockShippingInfoSectionType extends AbstractSectionType
{
    public function code(): string
    {
        return 'stock_shipping_info';
    }

    public function label(): string
    {
        return 'Stock & Shipping Info';
    }

    public function category(): string
    {
        return 'product_purchase';
    }

    public function configSchema(): array
    {
        return ['shipping_note' => 'string'];
    }

    public function defaultConfig(): array
    {
        return ['shipping_note' => 'Shipping and availability are resolved from platform settings and live inventory.'];
    }

    public function validationRules(): array
    {
        return [
            'shipping_note' => ['nullable', 'string'],
        ];
    }

    public function rendererView(): string
    {
        return 'theme-default::storefront.sections.stock-shipping-info';
    }
}
