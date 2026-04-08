<?php

declare(strict_types=1);

namespace ThemeCore\Services;

use ExperienceCms\Models\PageSection;
use ExperienceCms\Services\SectionRegistry;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use ThemeCore\Contracts\DataSourceResolverContract;
use ThemeCore\Contracts\SectionRendererContract;

class BladeSectionRenderer implements SectionRendererContract
{
    public function __construct(
        private readonly Factory $viewFactory,
        private readonly SectionRegistry $sectionRegistry,
        private readonly DataSourceResolverContract $dataSourceResolver,
    ) {}

    public function make(PageSection $section, array $context = []): View
    {
        $definition = $this->sectionRegistry->find((string) $section->sectionType?->code);
        $view = $definition?->view() ?? 'theme-default::storefront.sections.generic-section';

        return $this->viewFactory->make($view, $this->payload($section, $context));
    }

    public function payload(PageSection $section, array $context = []): array
    {
        $definition = $this->sectionRegistry->find((string) $section->sectionType?->code);
        $defaults = $definition?->defaultSettings() ?? [];
        $settings = array_replace_recursive($defaults, $section->settings_json ?? []);

        return [
            'section' => $section,
            'definition' => $definition,
            'settings' => $settings,
            'items' => $this->dataSourceResolver->resolve($section, $context),
            'context' => $context,
        ];
    }
}
