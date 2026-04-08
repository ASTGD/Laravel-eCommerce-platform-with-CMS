<?php

declare(strict_types=1);

namespace ExperienceCms\SectionTypes;

class RichTextSectionType extends AbstractSectionType
{
    public function code(): string
    {
        return 'rich_text';
    }

    public function name(): string
    {
        return 'Rich Text';
    }

    public function category(): string
    {
        return 'content';
    }

    public function view(): string
    {
        return 'theme-default::storefront.sections.rich-text';
    }

    public function configSchema(): array
    {
        return [
            ['key' => 'eyebrow', 'label' => 'Eyebrow', 'type' => 'text'],
            ['key' => 'headline', 'label' => 'Headline', 'type' => 'text'],
            ['key' => 'body', 'label' => 'Body HTML', 'type' => 'textarea'],
        ];
    }

    public function defaultSettings(): array
    {
        return [
            'eyebrow' => 'Structured content',
            'headline' => 'Bounded flexibility keeps installs maintainable.',
            'body' => '<p>Templates, sections, presets, and global areas provide repeatable control without handing layout internals to admins.</p>',
        ];
    }

    public function validationRules(): array
    {
        return [
            'eyebrow' => ['nullable', 'string', 'max:100'],
            'headline' => ['required', 'string', 'max:140'],
            'body' => ['required', 'string'],
        ];
    }

    public function supportedDataSources(): array
    {
        return ['selected_content_entries'];
    }
}
