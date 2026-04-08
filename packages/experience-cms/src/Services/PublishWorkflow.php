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
        $page->loadMissing(['template', 'sections.components', 'sections.sectionType']);

        $page->forceFill([
            'status'       => 'published',
            'published_at' => now(),
        ])->save();

        PageVersion::query()->create([
            'page_id'         => $page->getKey(),
            'version_number'  => ((int) $page->versions()->max('version_number')) + 1,
            'snapshot_json'   => [
                'page'     => Arr::only($page->toArray(), ['id', 'title', 'slug', 'type', 'template_id', 'status', 'published_at']),
                'template' => $page->template?->toArray(),
                'sections' => $page->sections->map(fn ($section) => $section->toArray())->all(),
            ],
            'note'            => $note ?: 'Published from admin workflow.',
            'created_by'      => auth('admin')->id(),
        ]);

        return $page->fresh(['template', 'sections.sectionType']);
    }
}
