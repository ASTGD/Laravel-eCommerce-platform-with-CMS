<?php

namespace Platform\ExperienceCms\ComponentTypes;

class BadgeListComponentType extends AbstractComponentType
{
    public function code(): string
    {
        return 'badge_list';
    }

    public function label(): string
    {
        return 'Badge List';
    }

    public function configSchema(): array
    {
        return ['badges' => 'array'];
    }

    public function defaultConfig(): array
    {
        return [
            'badges' => [
                ['label' => 'Structured'],
                ['label' => 'Commerce-ready'],
            ],
        ];
    }

    public function validationRules(): array
    {
        return [
            'badges' => ['array'],
            'badges.*.label' => ['required', 'string', 'max:120'],
        ];
    }

    public function rendererView(): string
    {
        return 'theme-default::storefront.components.badge-list';
    }
}
