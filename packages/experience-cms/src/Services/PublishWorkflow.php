<?php

namespace Platform\ExperienceCms\Services;

use Illuminate\Support\Arr;
use Platform\ExperienceCms\Contracts\PublishWorkflowContract;
use Platform\ExperienceCms\Models\Page;
use Platform\ExperienceCms\Models\PageVersion;

class PublishWorkflow implements PublishWorkflowContract
{
    public function publish(Page $page, ?string $note = null): Page
    {
        return $this->transition(
            $page,
            Page::STATUS_PUBLISHED,
            now(),
            $note ?: 'Published from admin workflow.'
        );
    }

    public function unpublish(Page $page, ?string $note = null): Page
    {
        return $this->transition(
            $page,
            Page::STATUS_DRAFT,
            null,
            $note ?: 'Reverted to draft from admin workflow.'
        );
    }

    protected function transition(Page $page, string $status, mixed $publishedAt, string $note): Page
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

        $page->forceFill([
            'status' => $status,
            'published_at' => $publishedAt,
        ])->save();

        PageVersion::query()->create([
            'page_id' => $page->getKey(),
            'version_number' => ((int) $page->versions()->max('version_number')) + 1,
            'snapshot_json' => [
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
            ],
            'note' => $note,
            'created_by' => auth('admin')->id(),
        ]);

        return $page->fresh([
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
    }
}
