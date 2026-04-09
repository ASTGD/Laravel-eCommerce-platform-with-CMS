<?php

namespace Platform\ExperienceCms\SectionTypes;

class FaqBlockSectionType extends AbstractSectionType
{
    public function code(): string
    {
        return 'faq_block';
    }

    public function label(): string
    {
        return 'FAQ Block';
    }

    public function category(): string
    {
        return 'content';
    }

    public function configSchema(): array
    {
        return ['headline' => 'string', 'items' => 'array'];
    }

    public function defaultConfig(): array
    {
        return [
            'headline' => 'Frequently Asked Questions',
            'items' => [
                ['question' => 'How is this configured?', 'answer' => 'Through approved CMS blocks.'],
            ],
        ];
    }

    public function validationRules(): array
    {
        return [
            'headline' => ['nullable', 'string', 'max:255'],
            'items' => ['array'],
            'items.*.question' => ['required', 'string', 'max:255'],
            'items.*.answer' => ['required', 'string'],
        ];
    }

    public function allowedDataSources(): array
    {
        return ['selected_content_entries'];
    }

    public function rendererView(): string
    {
        return 'theme-default::storefront.sections.faq-block';
    }
}
