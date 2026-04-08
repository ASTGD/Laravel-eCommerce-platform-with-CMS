<?php

declare(strict_types=1);

namespace ExperienceCms\SectionTypes;

class FeaturedProductsSectionType extends AbstractSectionType
{
    public function code(): string
    {
        return 'featured_products';
    }

    public function name(): string
    {
        return 'Featured Products';
    }

    public function category(): string
    {
        return 'catalog';
    }

    public function view(): string
    {
        return 'theme-default::storefront.sections.featured-products';
    }

    public function configSchema(): array
    {
        return [
            ['key' => 'headline', 'label' => 'Headline', 'type' => 'text'],
            ['key' => 'body', 'label' => 'Body', 'type' => 'textarea'],
            ['key' => 'items', 'label' => 'Manual Product Items JSON', 'type' => 'json'],
        ];
    }

    public function defaultSettings(): array
    {
        return [
            'headline' => 'Featured products',
            'body' => 'A curated selection that proves the section and preview pipeline.',
            'items' => [],
        ];
    }

    public function validationRules(): array
    {
        return [
            'headline' => ['required', 'string', 'max:120'],
            'body' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
        ];
    }

    public function supportedDataSources(): array
    {
        return ['manual_products', 'featured_products', 'best_sellers', 'new_arrivals', 'discounted_products'];
    }
}
