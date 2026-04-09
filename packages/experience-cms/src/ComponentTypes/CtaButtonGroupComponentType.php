<?php

namespace Platform\ExperienceCms\ComponentTypes;

class CtaButtonGroupComponentType extends AbstractComponentType
{
    public function code(): string
    {
        return 'cta_button_group';
    }

    public function label(): string
    {
        return 'CTA Button Group';
    }

    public function configSchema(): array
    {
        return ['buttons' => 'array'];
    }

    public function defaultConfig(): array
    {
        return [
            'buttons' => [
                ['label' => 'Shop now', 'url' => '/'],
                ['label' => 'Learn more', 'url' => '/pages/about'],
            ],
        ];
    }

    public function validationRules(): array
    {
        return [
            'buttons' => ['array', 'min:1'],
            'buttons.*.label' => ['required', 'string', 'max:120'],
            'buttons.*.url' => ['required', 'string', 'max:255'],
        ];
    }

    public function rendererView(): string
    {
        return 'theme-default::storefront.components.cta-button-group';
    }
}
