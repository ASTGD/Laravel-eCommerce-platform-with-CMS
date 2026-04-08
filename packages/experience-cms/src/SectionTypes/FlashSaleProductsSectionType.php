<?php

declare(strict_types=1);

namespace ExperienceCms\SectionTypes;

class FlashSaleProductsSectionType extends AbstractSectionType
{
    public function code(): string
    {
        return 'flash_sale_products';
    }

    public function name(): string
    {
        return 'Flash Sale Products';
    }

    public function category(): string
    {
        return 'catalog';
    }

    public function view(): string
    {
        return 'theme-default::storefront.sections.generic-section';
    }

    public function configSchema(): array
    {
        return [['key' => 'headline', 'label' => 'Headline', 'type' => 'text']];
    }

    public function defaultSettings(): array
    {
        return ['headline' => 'Flash sale products'];
    }

    public function validationRules(): array
    {
        return ['headline' => ['required', 'string', 'max:120']];
    }

    public function supportedDataSources(): array
    {
        return ['discounted_products'];
    }
}
