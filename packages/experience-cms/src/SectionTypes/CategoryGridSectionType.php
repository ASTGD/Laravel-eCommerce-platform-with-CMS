<?php

namespace Platform\ExperienceCms\SectionTypes;

class CategoryGridSectionType extends AbstractSectionType
{
    public function code(): string
    {
        return 'category_grid';
    }

    public function label(): string
    {
        return 'Category Grid';
    }

    public function category(): string
    {
        return 'catalog';
    }

    public function configSchema(): array
    {
        return ['columns' => 'integer'];
    }

    public function defaultConfig(): array
    {
        return ['columns' => 4];
    }

    public function validationRules(): array
    {
        return ['columns' => ['integer', 'min:2', 'max:6']];
    }

    public function allowedDataSources(): array
    {
        return ['manual_categories'];
    }

    public function rendererView(): string
    {
        return 'theme-default::storefront.sections.generic-section';
    }
}
