<?php

namespace Platform\ExperienceCms\Services;

use Illuminate\Support\Facades\DB;
use Platform\ExperienceCms\Models\Page;
use Platform\ExperienceCms\Models\PageSection;
use Platform\ExperienceCms\Models\SectionType;
use Platform\SeoTools\Models\SeoMeta;

class PageEditor
{
    public function __construct(protected SectionTypeRegistry $sectionTypes) {}

    public function create(array $attributes, array $seoPayload = [], array $sections = []): Page
    {
        return $this->persist(new Page([
            'status' => Page::STATUS_DRAFT,
        ]), $attributes, $seoPayload, $sections, true);
    }

    public function update(Page $page, array $attributes, array $seoPayload = [], array $sections = []): Page
    {
        return $this->persist($page, $attributes, $seoPayload, $sections, false);
    }

    protected function persist(Page $page, array $attributes, array $seoPayload, array $sections, bool $isNew): Page
    {
        return DB::transaction(function () use ($page, $attributes, $seoPayload, $sections, $isNew) {
            $page->fill($attributes);

            if ($isNew) {
                $page->created_by = auth('admin')->id();
            }

            $page->updated_by = auth('admin')->id();
            $page->save();

            $seoMeta = $this->syncSeoMeta($page->seoMeta, $seoPayload);

            $page->forceFill([
                'seo_meta_id' => $seoMeta?->getKey(),
            ])->save();

            $this->syncSections($page, $sections);

            return $page->fresh([
                'seoMeta',
                'template.areas',
                'sections.sectionType',
                'sections.templateArea',
                'headerConfig',
                'footerConfig',
                'menu.items.children',
                'themePreset',
            ]);
        });
    }

    protected function syncSeoMeta(?SeoMeta $seoMeta, array $payload): ?SeoMeta
    {
        $payload = array_filter($payload, fn ($value) => $value !== null && $value !== '' && $value !== []);

        if ($payload === []) {
            $seoMeta?->delete();

            return null;
        }

        $seoMeta ??= new SeoMeta;
        $seoMeta->fill($payload);
        $seoMeta->save();

        return $seoMeta;
    }

    protected function syncSections(Page $page, array $sections): void
    {
        $existing = $page->sections()->get()->keyBy('id');
        $seenIds = [];
        $template = $page->template()->with('areas')->first();
        $defaultAreaId = $template?->areas->sortBy('sort_order')->first()?->id;

        foreach ($sections as $sectionData) {
            $section = $sectionData['id']
                ? $existing->get($sectionData['id'], new PageSection(['page_id' => $page->getKey()]))
                : new PageSection(['page_id' => $page->getKey()]);

            $definition = null;

            if ($sectionData['section_type_id']) {
                $code = optional($section->sectionType)->code;

                if (! $code || (int) $section->section_type_id !== (int) $sectionData['section_type_id']) {
                    $code = (string) optional(SectionType::query()->find($sectionData['section_type_id']))?->code;
                }

                if ($code) {
                    $definition = $this->sectionTypes->find($code);
                }
            }

            $section->fill([
                'template_area_id' => $sectionData['template_area_id'] ?: $defaultAreaId,
                'section_type_id' => $sectionData['section_type_id'],
                'sort_order' => $sectionData['sort_order'],
                'title' => $sectionData['title'] ?: null,
                'settings_json' => array_replace($definition?->defaultConfig() ?? [], $sectionData['settings_json']),
                'visibility_rules_json' => [],
                'data_source_type' => $sectionData['data_source_type'],
                'data_source_payload_json' => $sectionData['data_source_payload_json'],
                'is_active' => $sectionData['is_active'],
            ])->save();

            $seenIds[] = $section->getKey();
        }

        $page->sections()
            ->when($seenIds !== [], fn ($query) => $query->whereNotIn('id', $seenIds), fn ($query) => $query)
            ->delete();
    }
}
