<?php

namespace Platform\ExperienceCms\Services;

use Illuminate\Support\Collection;
use Platform\ExperienceCms\Contracts\ContentEntryResolverContract;
use Platform\ExperienceCms\Models\ContentEntry;

class ContentEntryResolver implements ContentEntryResolverContract
{
    public function resolve(array $payload = [], array $context = []): Collection
    {
        $ids = collect($payload['content_entry_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return ContentEntry::query()
            ->when(
                ! ($context['preview'] ?? false),
                fn ($query) => $query->where('status', ContentEntry::STATUS_PUBLISHED)
            )
            ->whereIn('id', $ids->all())
            ->get()
            ->sortBy(fn (ContentEntry $entry) => $ids->search($entry->id))
            ->values();
    }
}
