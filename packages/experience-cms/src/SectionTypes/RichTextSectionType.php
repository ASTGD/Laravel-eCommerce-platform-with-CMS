<?php

namespace Platform\ExperienceCms\SectionTypes;

class RichTextSectionType extends AbstractSectionType
{
    public function code(): string
    {
        return 'rich_text';
    }

    public function label(): string
    {
        return 'Rich Text';
    }

    public function category(): string
    {
        return 'content';
    }

    public function configSchema(): array
    {
        return ['content' => 'text'];
    }

    public function defaultConfig(): array
    {
        return ['content' => 'Structured rich text content.'];
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
        return 'theme-default::storefront.sections.rich-text';
    }
}
