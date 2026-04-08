<?php

declare(strict_types=1);

namespace ExperienceCms\SectionTypes;

class CategoryGridSectionType extends AbstractSectionType
{
    public function code(): string
    {
        return 'category_grid';
    }

    public function name(): string
    {
        return 'Category Grid';
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
        return [['key' => 'headline', 'label' => 'Headline', 'type' => 'text'], ['key' => 'items', 'label' => 'Category Items JSON', 'type' => 'json']];
    }

    public function defaultSettings(): array
    {
        return ['headline' => 'Browse categories', 'items' => []];
    }

    public function validationRules(): array
    {
        return ['headline' => ['required', 'string', 'max:120'], 'items' => ['nullable', 'array']];
    }

    public function supportedDataSources(): array
    {
        return ['manual_categories'];
    }
}
