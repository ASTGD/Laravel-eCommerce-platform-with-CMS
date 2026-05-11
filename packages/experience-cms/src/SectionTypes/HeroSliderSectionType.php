<?php

namespace Platform\ExperienceCms\SectionTypes;

class HeroSliderSectionType extends AbstractSectionType
{
    public function code(): string
    {
        return 'hero_slider';
    }

    public function label(): string
    {
        return 'Hero Slider';
    }

    public function category(): string
    {
        return 'hero';
    }

    public function configSchema(): array
    {
        return [
            'slides' => 'array',
        ];
    }

    public function defaultConfig(): array
    {
        return [
            'slides' => [],
        ];
    }

    public function validationRules(): array
    {
        return [
            'slides' => ['array', 'max:5'],
            'slides.*.image' => ['required', 'string', 'max:2048'],
            'slides.*.title' => ['nullable', 'string', 'max:120'],
            'slides.*.link' => ['nullable', 'string', 'max:2048'],
        ];
    }

    public function allowedDataSources(): array
    {
        return [];
    }

    public function rendererView(): string
    {
        return 'theme-default::storefront.sections.hero-slider';
    }
}
