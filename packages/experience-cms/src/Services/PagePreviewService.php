<?php

namespace Platform\ExperienceCms\Services;

use Platform\CommerceCore\Contracts\DataSourceResolverContract;
use Platform\ExperienceCms\Contracts\ComponentTypeContract;
use Platform\ExperienceCms\Contracts\FooterResolverContract;
use Platform\ExperienceCms\Contracts\HeaderResolverContract;
use Platform\ExperienceCms\Contracts\MenuResolverContract;
use Platform\ExperienceCms\Contracts\PagePreviewServiceContract;
use Platform\ExperienceCms\Models\Page;
use Platform\ExperienceCms\Models\PageSection;
use Platform\ExperienceCms\Models\SectionComponent;
use Platform\ThemeCore\Contracts\ComponentRendererContract;
use Platform\ThemeCore\Contracts\SectionRendererContract;
use Platform\ThemeCore\Contracts\ThemePresetResolverContract;

class PagePreviewService implements PagePreviewServiceContract
{
    public function __construct(
        protected SectionTypeRegistry $sectionTypes,
        protected ComponentTypeRegistry $componentTypes,
        protected DataSourceResolverContract $dataSources,
        protected SectionRendererContract $sectionRenderer,
        protected ComponentRendererContract $componentRenderer,
        protected ThemePresetResolverContract $themePresetResolver,
        protected HeaderResolverContract $headerResolver,
        protected FooterResolverContract $footerResolver,
        protected MenuResolverContract $menuResolver,
    ) {}

    public function build(Page $page): array
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

        $sections = $page->sections
            ->where('is_active', true)
            ->map(function (PageSection $section) use ($page, $preset) {
                $definition = $this->sectionTypes->find((string) optional($section->sectionType)->code);
                $items = $section->data_source_type
                    ? $this->dataSources->resolve($section->data_source_type, $section->data_source_payload_json ?? [], ['page' => $page, 'section' => $section])
                    : collect();

                $payload = [
                    'page'    => $page,
                    'preset'  => $preset,
                    'section' => [
                        'id'       => $section->id,
                        'area'     => $section->templateArea?->code,
                        'code'     => optional($section->sectionType)->code,
                        'title'    => $section->title,
                        'settings' => array_replace($definition?->defaultConfig() ?? [], $section->settings_json ?? []),
                        'items'    => $items,
                        'components' => $section->components
                            ->where('is_active', true)
                            ->map(fn (SectionComponent $component) => $this->buildComponent($page, $preset, $component))
                            ->values(),
                    ],
                ];

                return [
                    'model' => $section,
                    'html'  => $this->sectionRenderer->render(
                        $definition?->rendererView() ?? 'theme-default::storefront.sections.generic-section',
                        $payload
                    ),
                ];
            })
            ->values();

        return compact('page', 'preset', 'header', 'footer', 'menu', 'sections');
    }

    protected function buildComponent(Page $page, mixed $preset, SectionComponent $component): array
    {
        /** @var ComponentTypeContract|null $definition */
        $definition = $this->componentTypes->find((string) optional($component->componentType)->code);

        $payload = [
            'page' => $page,
            'preset' => $preset,
            'component' => [
                'id' => $component->id,
                'code' => optional($component->componentType)->code,
                'settings' => array_replace($definition?->defaultConfig() ?? [], $component->settings_json ?? []),
            ],
        ];

        return [
            'model' => $component,
            'html' => $this->componentRenderer->render(
                $definition?->rendererView() ?? 'theme-default::storefront.components.generic',
                $payload
            ),
        ];
    }
}
