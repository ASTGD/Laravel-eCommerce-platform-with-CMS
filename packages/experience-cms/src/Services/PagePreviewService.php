<?php

namespace Platform\ExperienceCms\Services;

use Platform\CommerceCore\Contracts\DataSourceResolverContract;
use Platform\ExperienceCms\Contracts\FooterResolverContract;
use Platform\ExperienceCms\Contracts\HeaderResolverContract;
use Platform\ExperienceCms\Contracts\PagePreviewServiceContract;
use Platform\ExperienceCms\Models\Page;
use Platform\ExperienceCms\Models\PageSection;
use Platform\ThemeCore\Contracts\SectionRendererContract;
use Platform\ThemeCore\Contracts\ThemePresetResolverContract;

class PagePreviewService implements PagePreviewServiceContract
{
    public function __construct(
        protected SectionTypeRegistry $sectionTypes,
        protected DataSourceResolverContract $dataSources,
        protected SectionRendererContract $sectionRenderer,
        protected ThemePresetResolverContract $themePresetResolver,
        protected HeaderResolverContract $headerResolver,
        protected FooterResolverContract $footerResolver,
    ) {}

    public function build(Page $page): array
    {
        $page->loadMissing(['template.areas', 'sections.sectionType']);

        $preset = $this->themePresetResolver->resolve();
        $header = $this->headerResolver->resolve();
        $footer = $this->footerResolver->resolve();

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
                        'code'     => optional($section->sectionType)->code,
                        'title'    => $section->title,
                        'settings' => $section->settings_json ?? [],
                        'items'    => $items,
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

        return compact('page', 'preset', 'header', 'footer', 'sections');
    }
}
