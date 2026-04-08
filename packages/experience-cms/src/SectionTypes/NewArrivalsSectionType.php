<?php

declare(strict_types=1);

namespace ExperienceCms\SectionTypes;

class NewArrivalsSectionType extends AbstractSectionType
{
    public function code(): string
    {
        return 'new_arrivals';
    }

    public function name(): string
    {
        return 'New Arrivals';
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
        return ['headline' => 'New arrivals'];
    }

    public function validationRules(): array
    {
        return ['headline' => ['required', 'string', 'max:120']];
    }

    public function supportedDataSources(): array
    {
        return ['new_arrivals'];
    }
}
