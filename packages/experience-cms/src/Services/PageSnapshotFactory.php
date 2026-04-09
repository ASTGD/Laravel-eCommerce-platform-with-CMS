<?php

namespace Platform\ExperienceCms\Services;

use Illuminate\Support\Arr;
use Platform\ExperienceCms\Models\Page;

class PageSnapshotFactory
{
    public function make(Page $page): array
    {
        $page->loadMissing([
            'template.areas',
            'sections.components.componentType',
            'sections.sectionType',
            'sections.templateArea',
            'headerConfig',
            'footerConfig',
            'menu.items.children',
            'themePreset',
            'seoMeta',
        ]);

        return [
            'page' => Arr::only($page->toArray(), [
                'id',
                'title',
                'slug',
                'type',
                'template_id',
                'header_config_id',
                'footer_config_id',
                'menu_id',
                'theme_preset_id',
                'settings_json',
                'status',
                'published_at',
            ]),
            'template' => $page->template?->toArray(),
            'template_areas' => $page->template?->areas?->map->toArray()->all(),
            'seo_meta' => $page->seoMeta?->toArray(),
            'header' => $page->headerConfig?->toArray(),
            'footer' => $page->footerConfig?->toArray(),
            'menu' => $page->menu?->loadMissing('items.children')?->toArray(),
            'preset' => $page->themePreset?->toArray(),
            'sections' => $page->sections->map(function ($section) {
                return array_merge($section->toArray(), [
                    'section_type' => $section->sectionType?->toArray(),
                    'template_area' => $section->templateArea?->toArray(),
                    'components' => $section->components->map(function ($component) {
                        return array_merge($component->toArray(), [
                            'component_type' => $component->componentType?->toArray(),
                        ]);
                    })->all(),
                ]);
            })->all(),
        ];
    }
}
