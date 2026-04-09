<?php

namespace Platform\ExperienceCms\Services;

use Illuminate\Support\Collection;
use Platform\CommerceCore\Contracts\DataSourceResolverContract;
use Platform\ExperienceCms\Contracts\ComponentTypeContract;
use Platform\ExperienceCms\Contracts\ContentEntryResolverContract;
use Platform\ExperienceCms\Contracts\FooterResolverContract;
use Platform\ExperienceCms\Contracts\HeaderResolverContract;
use Platform\ExperienceCms\Contracts\MenuResolverContract;
use Platform\ExperienceCms\Contracts\SiteSettingsResolverContract;
use Platform\ExperienceCms\Models\Page;
use Platform\ExperienceCms\Models\PageSection;
use Platform\ExperienceCms\Models\SectionComponent;
use Platform\ThemeCore\Contracts\ComponentRendererContract;
use Platform\ThemeCore\Contracts\SectionRendererContract;
use Platform\ThemeCore\Contracts\ThemePresetResolverContract;

class StructuredPagePayloadBuilder
{
    public function __construct(
        protected SectionTypeRegistry $sectionTypes,
        protected ComponentTypeRegistry $componentTypes,
        protected DataSourceResolverContract $dataSources,
        protected ContentEntryResolverContract $contentEntries,
        protected SectionRendererContract $sectionRenderer,
        protected ComponentRendererContract $componentRenderer,
        protected ThemePresetResolverContract $themePresetResolver,
        protected HeaderResolverContract $headerResolver,
        protected FooterResolverContract $footerResolver,
        protected MenuResolverContract $menuResolver,
        protected SiteSettingsResolverContract $siteSettings,
    ) {}

    public function build(Page $page, array $context = []): array
    {
        $page->loadMissing([
            'template.areas',
            'sections.sectionType',
            'sections.templateArea',
            'sections.components.componentType',
            'headerConfig',
            'footerConfig',
            'menu.items.children',
            'themePreset',
            'seoMeta',
        ]);

        $preset = $this->themePresetResolver->resolve($page->themePreset?->code);
        $header = $this->headerResolver->resolve($page->headerConfig?->code);
        $footer = $this->footerResolver->resolve($page->footerConfig?->code);
        $menu = $this->menuResolver->resolve($page->menu?->code);
        $siteSettings = $this->siteSettings->all();

        $sections = $page->sections
            ->where('is_active', true)
            ->map(fn (PageSection $section) => $this->buildSection($page, $section, $preset, $siteSettings, $context))
            ->values();

        return compact('page', 'preset', 'header', 'footer', 'menu', 'siteSettings', 'sections', 'context');
    }

    protected function buildSection(Page $page, PageSection $section, mixed $preset, array $siteSettings, array $context): array
    {
        $definition = $this->sectionTypes->find((string) optional($section->sectionType)->code);
        $items = $this->resolveItems($section->data_source_type, $section->data_source_payload_json ?? [], [
            'page' => $page,
            'section' => $section,
        ] + $context);

        $payload = [
            'page' => $page,
            'preset' => $preset,
            'siteSettings' => $siteSettings,
            'context' => $context,
            ...$context,
            'section' => [
                'id' => $section->id,
                'area' => $section->templateArea?->code,
                'code' => optional($section->sectionType)->code,
                'title' => $section->title,
                'settings' => array_replace($definition?->defaultConfig() ?? [], $section->settings_json ?? []),
                'items' => $items,
                'components' => $section->components
                    ->where('is_active', true)
                    ->map(fn (SectionComponent $component) => $this->buildComponent($page, $preset, $siteSettings, $component, $context))
                    ->values()
                    ->all(),
            ],
        ];

        return [
            'model' => $section,
            'area' => $section->templateArea?->code,
            'code' => optional($section->sectionType)->code,
            'title' => $section->title,
            'settings' => $payload['section']['settings'],
            'items' => $items,
            'components' => $payload['section']['components'],
            'html' => $this->sectionRenderer->render(
                $definition?->rendererView() ?? 'theme-default::storefront.sections.generic-section',
                $payload
            ),
        ];
    }

    protected function buildComponent(Page $page, mixed $preset, array $siteSettings, SectionComponent $component, array $context): array
    {
        /** @var ComponentTypeContract|null $definition */
        $definition = $this->componentTypes->find((string) optional($component->componentType)->code);

        $payload = [
            'page' => $page,
            'preset' => $preset,
            'siteSettings' => $siteSettings,
            'context' => $context,
            ...$context,
            'component' => [
                'id' => $component->id,
                'code' => optional($component->componentType)->code,
                'settings' => array_replace($definition?->defaultConfig() ?? [], $component->settings_json ?? []),
            ],
        ];

        return [
            'model' => $component,
            'code' => optional($component->componentType)->code,
            'settings' => $payload['component']['settings'],
            'html' => $this->componentRenderer->render(
                $definition?->rendererView() ?? 'theme-default::storefront.components.generic',
                $payload
            ),
        ];
    }

    protected function resolveItems(?string $sourceType, array $payload, array $context): Collection
    {
        if (! $sourceType) {
            return collect();
        }

        if ($sourceType === 'selected_content_entries') {
            return $this->contentEntries->resolve($payload, $context);
        }

        return $this->dataSources->resolve($sourceType, $payload, $context);
    }
}
