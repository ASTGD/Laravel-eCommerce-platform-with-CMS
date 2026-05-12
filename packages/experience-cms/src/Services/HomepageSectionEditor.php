<?php

namespace Platform\ExperienceCms\Services;

use Illuminate\Support\Facades\DB;
use Platform\ExperienceCms\Models\Page;
use Platform\ExperienceCms\Models\PageSection;
use Platform\ExperienceCms\Models\SectionType;

class HomepageSectionEditor
{
    public function __construct(
        protected SectionTypeRegistry $sectionTypes,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $sections
     */
    public function sync(Page $page, array $sections): Page
    {
        return DB::transaction(function () use ($page, $sections): Page {
            $existing = $page->sections()
                ->with(['sectionType', 'templateArea'])
                ->get()
                ->keyBy('id');

            foreach ($sections as $sectionData) {
                $section = filled($sectionData['id'] ?? null)
                    ? $existing->get((int) $sectionData['id'])
                    : new PageSection(['page_id' => $page->getKey()]);

                if (! $section instanceof PageSection) {
                    continue;
                }

                $sectionType = SectionType::query()
                    ->where('code', $sectionData['section_code'])
                    ->first();

                if (! $sectionType) {
                    continue;
                }

                $definition = $this->sectionTypes->find($sectionType->code);
                $settings = array_replace(
                    $definition?->defaultConfig() ?? [],
                    $sectionData['settings_json'] ?? []
                );

                $section->fill([
                    'page_id' => $page->getKey(),
                    'template_area_id' => $this->templateAreaIdFor($page, $sectionType->code, $section),
                    'section_type_id' => $sectionType->getKey(),
                    'sort_order' => (int) ($sectionData['sort_order'] ?? 0),
                    'title' => filled($sectionData['title'] ?? null) ? $sectionData['title'] : $sectionType->name,
                    'settings_json' => $settings,
                    'visibility_rules_json' => $sectionData['visibility_rules_json'] ?? $section->visibility_rules_json ?? [],
                    'data_source_type' => $sectionData['data_source_type'] ?? $section->data_source_type,
                    'data_source_payload_json' => $sectionData['data_source_payload_json'] ?? $section->data_source_payload_json ?? [],
                    'is_active' => (bool) ($sectionData['is_active'] ?? false),
                ])->save();
            }

            $page->forceFill([
                'updated_by' => auth('admin')->id(),
            ])->save();

            return $page->fresh([
                'template.areas',
                'sections.sectionType',
                'sections.templateArea',
                'sections.components.componentType',
                'versions',
            ]);
        });
    }

    protected function templateAreaIdFor(Page $page, string $sectionCode, PageSection $section): ?int
    {
        if ($section->template_area_id) {
            return $section->template_area_id;
        }

        $template = $page->template()->with('areas')->first();

        if (! $template) {
            return null;
        }

        $preferredAreaCode = in_array($sectionCode, ['hero', 'hero_banner', 'hero_slider'], true) ? 'hero' : 'content';

        return $template->areas->firstWhere('code', $preferredAreaCode)?->getKey()
            ?? $template->areas->sortBy('sort_order')->first()?->getKey();
    }
}
