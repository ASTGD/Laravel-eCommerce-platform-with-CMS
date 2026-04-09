<?php

namespace Platform\ExperienceCms\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Platform\ExperienceCms\Contracts\PageVersionRestoreContract;
use Platform\ExperienceCms\Models\Page;
use Platform\ExperienceCms\Models\PageVersion;
class PageVersionRestoreService implements PageVersionRestoreContract
{
    public function __construct(
        protected PageEditor $pageEditor,
        protected PageSnapshotFactory $snapshots,
    ) {}

    public function restore(Page $page, PageVersion $version, ?string $note = null): Page
    {
        return DB::transaction(function () use ($page, $version, $note) {
            $currentSnapshot = $this->snapshots->make($page);
            $snapshot = $version->snapshot_json;

            PageVersion::query()->create([
                'page_id' => $page->getKey(),
                'version_number' => ((int) $page->versions()->max('version_number')) + 1,
                'snapshot_json' => $currentSnapshot,
                'note' => $note ?: sprintf('Snapshot before restoring version %d.', $version->version_number),
                'created_by' => auth('admin')->id(),
            ]);

            $pagePayload = Arr::except($snapshot['page'] ?? [], ['id', 'seo_meta_id', 'published_at']);
            $pagePayload['published_at'] = $snapshot['page']['published_at'] ?? null;

            $restoredPage = $this->pageEditor->update(
                $page,
                $pagePayload,
                Arr::except($snapshot['seo_meta'] ?? [], ['id', 'created_at', 'updated_at']),
                $this->restoredSections($snapshot['sections'] ?? [])
            );

            return $restoredPage->fresh([
                'template.areas',
                'sections.sectionType',
                'sections.templateArea',
                'sections.components.componentType',
                'headerConfig',
                'footerConfig',
                'menu.items.children',
                'themePreset',
                'seoMeta',
                'versions',
            ]);
        });
    }

    protected function restoredSections(array $sections): array
    {
        return collect($sections)
            ->map(function (array $section) {
                return [
                    'id' => null,
                    'template_area_id' => $section['template_area_id'] ?? null,
                    'section_type_id' => $section['section_type_id'] ?? null,
                    'sort_order' => (int) ($section['sort_order'] ?? 0),
                    'title' => (string) ($section['title'] ?? ''),
                    'settings_json' => $section['settings_json'] ?? [],
                    'data_source_type' => $section['data_source_type'] ?? null,
                    'data_source_payload_json' => $section['data_source_payload_json'] ?? [],
                    'is_active' => (bool) ($section['is_active'] ?? false),
                    'components' => collect($section['components'] ?? [])->map(function (array $component) {
                        return [
                            'id' => null,
                            'component_type_id' => $component['component_type_id'] ?? null,
                            'sort_order' => (int) ($component['sort_order'] ?? 0),
                            'settings_json' => $component['settings_json'] ?? [],
                            'data_source_type' => $component['data_source_type'] ?? null,
                            'data_source_payload_json' => $component['data_source_payload_json'] ?? [],
                            'is_active' => (bool) ($component['is_active'] ?? false),
                        ];
                    })->all(),
                ];
            })
            ->all();
    }
}
