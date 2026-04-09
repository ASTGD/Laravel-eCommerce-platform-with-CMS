<?php

namespace Platform\ExperienceCms\ComponentTypes;

class HeadlineComponentType extends AbstractComponentType
{
    public function code(): string
    {
        return 'headline';
    }

    public function label(): string
    {
        return 'Headline';
    }

    public function configSchema(): array
    {
        return ['content' => 'string'];
    }

    public function defaultConfig(): array
    {
        return ['content' => 'Structured headline'];
    }

    public function validationRules(): array
    {
        return [
            'content' => ['required', 'string', 'max:255'],
        ];
    }

    public function rendererView(): string
    {
        return 'theme-default::storefront.components.headline';
    }
}
