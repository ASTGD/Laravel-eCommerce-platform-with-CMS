<?php

namespace Platform\ExperienceCms\ComponentTypes;

class LinkListComponentType extends AbstractComponentType
{
    public function code(): string
    {
        return 'link_list';
    }

    public function label(): string
    {
        return 'Link List';
    }

    public function configSchema(): array
    {
        return ['links' => 'array'];
    }

    public function defaultConfig(): array
    {
        return [
            'links' => [
                ['label' => 'About', 'url' => '/pages/about'],
                ['label' => 'Support', 'url' => '/contact-us'],
            ],
        ];
    }

    public function validationRules(): array
    {
        return [
            'links' => ['array'],
            'links.*.label' => ['required', 'string', 'max:120'],
            'links.*.url' => ['required', 'string', 'max:255'],
        ];
    }

    public function rendererView(): string
    {
        return 'theme-default::storefront.components.link-list';
    }
}
