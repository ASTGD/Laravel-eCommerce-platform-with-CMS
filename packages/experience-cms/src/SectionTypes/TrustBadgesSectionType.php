<?php

namespace Platform\ExperienceCms\SectionTypes;

class TrustBadgesSectionType extends AbstractSectionType
{
    public function code(): string
    {
        return 'trust_badges';
    }

    public function label(): string
    {
        return 'Trust Badges';
    }

    public function category(): string
    {
        return 'supporting';
    }

    public function configSchema(): array
    {
        return ['headline' => 'string', 'badges' => 'array'];
    }

    public function defaultConfig(): array
    {
        return ['headline' => 'Why shop with us', 'badges' => []];
    }

    public function validationRules(): array
    {
        return [
            'headline' => ['nullable', 'string', 'max:255'],
            'badges' => ['array'],
            'badges.*.label' => ['required', 'string', 'max:120'],
        ];
    }

    public function rendererView(): string
    {
        return 'theme-default::storefront.sections.trust-badges';
    }
}
