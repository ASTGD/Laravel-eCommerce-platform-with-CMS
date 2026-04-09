<?php

namespace Platform\ExperienceCms\SectionTypes;

class CategoryIntroSectionType extends AbstractSectionType
{
    public function code(): string
    {
        return 'category_intro';
    }

    public function label(): string
    {
        return 'Category Intro';
    }

    public function category(): string
    {
        return 'category';
    }

    public function configSchema(): array
    {
        return ['headline' => 'string', 'content' => 'text'];
    }

    public function defaultConfig(): array
    {
        return [
            'headline' => 'Category Introduction',
            'content' => 'Structured category messaging aligned to the selected catalog collection.',
        ];
    }

    public function validationRules(): array
    {
        return [
            'headline' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
        ];
    }

    public function allowedDataSources(): array
    {
        return ['selected_content_entries'];
    }

    public function supportsComponents(): bool
    {
        return true;
    }

    public function rendererView(): string
    {
        return 'theme-default::storefront.sections.category-intro';
    }
}
