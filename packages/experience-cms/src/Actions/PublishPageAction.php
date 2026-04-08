<?php

declare(strict_types=1);

namespace ExperienceCms\Actions;

use ExperienceCms\Enums\PageStatus;
use ExperienceCms\Models\Page;

class PublishPageAction
{
    public function execute(Page $page, ?int $actorId = null, ?string $note = null): Page
    {
        $page->loadMissing('template', 'sections.sectionType', 'sections.components.componentType');

        $versionNumber = ((int) $page->versions()->max('version_number')) + 1;

        $page->versions()->create([
            'version_number' => $versionNumber,
            'snapshot_json' => [
                'page' => $page->only([
                    'title',
                    'slug',
                    'type',
                    'status',
                    'published_at',
                ]),
                'template' => $page->template?->only(['id', 'name', 'code', 'page_type']),
                'sections' => $page->sections->map(fn ($section): array => [
                    'title' => $section->title,
                    'sort_order' => $section->sort_order,
                    'type' => $section->sectionType?->code,
                    'settings' => $section->settings_json,
                    'data_source_type' => $section->data_source_type,
                    'data_source_payload' => $section->data_source_payload_json,
                ])->values()->all(),
            ],
            'note' => $note,
            'created_by' => $actorId,
        ]);

        $page->forceFill([
            'status' => PageStatus::Published,
            'published_at' => now(),
            'updated_by' => $actorId,
        ])->save();

        return $page->refresh();
    }
}
