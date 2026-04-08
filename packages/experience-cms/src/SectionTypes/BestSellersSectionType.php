<?php

declare(strict_types=1);

namespace ExperienceCms\SectionTypes;

class BestSellersSectionType extends AbstractSectionType
{
    public function code(): string
    {
        return 'best_sellers';
    }

    public function name(): string
    {
        return 'Best Sellers';
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
        return ['headline' => 'Best sellers'];
    }

    public function validationRules(): array
    {
        return ['headline' => ['required', 'string', 'max:120']];
    }

    public function supportedDataSources(): array
    {
        return ['best_sellers'];
    }
}
