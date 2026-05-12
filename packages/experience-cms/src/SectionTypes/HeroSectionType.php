<?php

namespace Platform\ExperienceCms\SectionTypes;

class HeroSectionType extends AbstractSectionType
{
    public function code(): string
    {
        return 'hero';
    }

    public function label(): string
    {
        return 'Hero';
    }

    public function category(): string
    {
        return 'hero';
    }

    public function configSchema(): array
    {
        return [
            'mode' => 'string',
            'slides' => [
                'type' => 'array',
                'max' => 5,
                'fields' => [
                    'image' => 'image',
                    'title' => 'string',
                    'headline' => 'string',
                    'body' => 'text',
                    'primary_cta_label' => 'string',
                    'primary_cta_url' => 'string',
                    'secondary_cta_label' => 'string',
                    'secondary_cta_url' => 'string',
                ],
            ],
        ];
    }

    public function defaultConfig(): array
    {
        return [
            'mode' => 'static',
            'slides' => [],
        ];
    }

    public function validationRules(): array
    {
        return [
            'mode' => ['required', 'in:static,slider'],
            'slides' => ['required', 'array', 'min:1', 'max:5'],
            'slides.*.image' => ['required', 'string', 'max:2048'],
            'slides.*.title' => ['nullable', 'string', 'max:120'],
            'slides.*.headline' => ['nullable', 'string', 'max:255'],
            'slides.*.body' => ['nullable', 'string', 'max:1000'],
            'slides.*.primary_cta_label' => ['nullable', 'string', 'max:80'],
            'slides.*.primary_cta_url' => ['nullable', 'string', 'max:2048'],
            'slides.*.secondary_cta_label' => ['nullable', 'string', 'max:80'],
            'slides.*.secondary_cta_url' => ['nullable', 'string', 'max:2048'],
        ];
    }

    public function rendererView(): string
    {
        return 'theme-default::storefront.sections.hero';
    }
}
