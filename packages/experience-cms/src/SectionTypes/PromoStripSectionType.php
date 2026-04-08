<?php

declare(strict_types=1);

namespace ExperienceCms\SectionTypes;

class PromoStripSectionType extends AbstractSectionType
{
    public function code(): string
    {
        return 'promo_strip';
    }

    public function name(): string
    {
        return 'Promo Strip';
    }

    public function category(): string
    {
        return 'announcement';
    }

    public function view(): string
    {
        return 'theme-default::storefront.sections.generic-section';
    }

    public function configSchema(): array
    {
        return [
            ['key' => 'message', 'label' => 'Message', 'type' => 'text'],
            ['key' => 'link_label', 'label' => 'Link Label', 'type' => 'text'],
            ['key' => 'link_url', 'label' => 'Link URL', 'type' => 'text'],
        ];
    }

    public function defaultSettings(): array
    {
        return ['message' => 'Fast launch. Repeatable installs. Structured content.', 'link_label' => 'Explore', 'link_url' => '#'];
    }

    public function validationRules(): array
    {
        return ['message' => ['required', 'string', 'max:160'], 'link_label' => ['nullable', 'string', 'max:50'], 'link_url' => ['nullable', 'string', 'max:255']];
    }

    public function supportedDataSources(): array
    {
        return [];
    }
}
