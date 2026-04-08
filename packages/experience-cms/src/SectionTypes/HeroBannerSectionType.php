<?php

declare(strict_types=1);

namespace ExperienceCms\SectionTypes;

class HeroBannerSectionType extends AbstractSectionType
{
    public function code(): string
    {
        return 'hero_banner';
    }

    public function name(): string
    {
        return 'Hero Banner';
    }

    public function category(): string
    {
        return 'hero';
    }

    public function view(): string
    {
        return 'theme-default::storefront.sections.hero-banner';
    }

    public function configSchema(): array
    {
        return [
            ['key' => 'eyebrow', 'label' => 'Eyebrow', 'type' => 'text'],
            ['key' => 'headline', 'label' => 'Headline', 'type' => 'text'],
            ['key' => 'body', 'label' => 'Body', 'type' => 'textarea'],
            ['key' => 'primary_label', 'label' => 'Primary Button Label', 'type' => 'text'],
            ['key' => 'primary_url', 'label' => 'Primary Button URL', 'type' => 'text'],
            ['key' => 'secondary_label', 'label' => 'Secondary Button Label', 'type' => 'text'],
            ['key' => 'secondary_url', 'label' => 'Secondary Button URL', 'type' => 'text'],
            ['key' => 'image_url', 'label' => 'Image URL', 'type' => 'text'],
        ];
    }

    public function defaultSettings(): array
    {
        return [
            'eyebrow' => 'Structured commerce platform',
            'headline' => 'Launch a standalone storefront without rebuilding the platform.',
            'body' => 'Compose homepage content from approved sections, keep visual variation inside presets, and ship repeatable installs.',
            'primary_label' => 'Browse featured products',
            'primary_url' => '#featured-products',
            'secondary_label' => 'Read the platform brief',
            'secondary_url' => '/pages/about',
            'image_url' => 'https://images.unsplash.com/photo-1523381210434-271e8be1f52b?auto=format&fit=crop&w=1200&q=80',
        ];
    }

    public function validationRules(): array
    {
        return [
            'eyebrow' => ['nullable', 'string', 'max:100'],
            'headline' => ['required', 'string', 'max:160'],
            'body' => ['nullable', 'string'],
            'primary_label' => ['nullable', 'string', 'max:60'],
            'primary_url' => ['nullable', 'string', 'max:255'],
            'secondary_label' => ['nullable', 'string', 'max:60'],
            'secondary_url' => ['nullable', 'string', 'max:255'],
            'image_url' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function supportedDataSources(): array
    {
        return [];
    }
}
