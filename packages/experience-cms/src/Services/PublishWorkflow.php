<?php

namespace Platform\ExperienceCms\Services;

use Illuminate\Support\Arr;
use Platform\ExperienceCms\Contracts\PublishWorkflowContract;
use Platform\ExperienceCms\Models\Page;
use Platform\ExperienceCms\Models\PageVersion;

class PublishWorkflow implements PublishWorkflowContract
{
    public function __construct(protected PageSnapshotFactory $snapshots) {}

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
        $page->forceFill([
            'status' => $status,
            'published_at' => $publishedAt,
        ])->save();

        PageVersion::query()->create([
            'page_id' => $page->getKey(),
            'version_number' => ((int) $page->versions()->max('version_number')) + 1,
            'snapshot_json' => $this->snapshots->make($page),
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
