<?php

namespace Platform\ExperienceCms\SectionTypes;

class PromoStripSectionType extends AbstractSectionType
{
    public function code(): string
    {
        return 'promo_strip';
    }

    public function label(): string
    {
        return 'Promo Strip';
    }

    public function category(): string
    {
        return 'merchandising';
    }

    public function configSchema(): array
    {
        return ['content' => 'string'];
    }

    public function defaultConfig(): array
    {
        return ['content' => 'Limited-time offer'];
    }

    public function validationRules(): array
    {
        return ['content' => ['required', 'string']];
    }

    public function allowedDataSources(): array
    {
        return [];
    }

    public function rendererView(): string
    {
        return 'theme-default::storefront.sections.generic-section';
    }
}
