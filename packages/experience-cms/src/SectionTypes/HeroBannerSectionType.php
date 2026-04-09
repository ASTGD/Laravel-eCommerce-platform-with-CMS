<?php

namespace Platform\ExperienceCms\SectionTypes;

class HeroBannerSectionType extends AbstractSectionType
{
    public function code(): string
    {
        return 'hero_banner';
    }

    public function label(): string
    {
        return 'Hero Banner';
    }

    public function category(): string
    {
        return 'hero';
    }

    public function configSchema(): array
    {
        return [
            'eyebrow' => 'string',
            'headline' => 'string',
            'body' => 'text',
            'primary_cta_label' => 'string',
            'primary_cta_url' => 'string',
            'secondary_cta_label' => 'string',
            'secondary_cta_url' => 'string',
        ];
    }

    public function defaultConfig(): array
    {
        return [
            'eyebrow' => 'Structured Commerce',
            'headline' => 'Launch a reusable storefront product.',
            'body' => 'A structured CMS and theme system layered on the commerce foundation.',
            'primary_cta_label' => 'Shop now',
            'primary_cta_url' => '/catalog',
            'secondary_cta_label' => 'Learn more',
            'secondary_cta_url' => '/pages/about',
        ];
    }

    public function validationRules(): array
    {
        return [
            'headline' => ['required', 'string', 'max:255'],
        ];
    }

    public function allowedDataSources(): array
    {
        return [];
    }

    public function supportsComponents(): bool
    {
        return true;
    }

    public function rendererView(): string
    {
        return 'theme-default::storefront.sections.hero-banner';
    }
}
