<?php

namespace Platform\ExperienceCms\SectionTypes;

class NewArrivalsSectionType extends AbstractSectionType
{
    public function code(): string
    {
        return 'new_arrivals';
    }

    public function label(): string
    {
        return 'New Arrivals';
    }

    public function category(): string
    {
        return 'catalog';
    }

    public function configSchema(): array
    {
        return ['limit' => 'integer'];
    }

    public function defaultConfig(): array
    {
        return ['limit' => 8];
    }

    public function validationRules(): array
    {
        return ['limit' => ['integer', 'min:1', 'max:24']];
    }

    public function allowedDataSources(): array
    {
        return ['new_arrivals'];
    }

    public function rendererView(): string
    {
        return 'theme-default::storefront.sections.featured-products';
    }
}
