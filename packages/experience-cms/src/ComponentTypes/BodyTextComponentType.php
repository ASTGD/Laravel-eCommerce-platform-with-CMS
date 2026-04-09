<?php

namespace Platform\ExperienceCms\ComponentTypes;

class BodyTextComponentType extends AbstractComponentType
{
    public function code(): string
    {
        return 'body_text';
    }

    public function label(): string
    {
        return 'Body Text';
    }

    public function configSchema(): array
    {
        return ['content' => 'text'];
    }

    public function defaultConfig(): array
    {
        return ['content' => 'Structured supporting copy.'];
    }

    public function validationRules(): array
    {
        return [
            'content' => ['required', 'string'],
        ];
    }

    public function rendererView(): string
    {
        return 'theme-default::storefront.components.body-text';
    }
}
