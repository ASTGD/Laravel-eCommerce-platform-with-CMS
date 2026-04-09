<?php

namespace Platform\ExperienceCms\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Platform\ExperienceCms\Http\Requests\Admin\ContentEntryRequest;
use Platform\ExperienceCms\Models\ContentEntry;

class ContentEntryController extends Controller
{
    public function index(): View
    {
        return view('experience-cms::admin.content-entries.index', [
            'contentEntries' => ContentEntry::query()->orderBy('type')->orderBy('title')->get(),
        ]);
    }

    public function create(): View
    {
        return view('experience-cms::admin.content-entries.form', [
            'contentEntry' => new ContentEntry(['status' => 'draft']),
        ]);
    }

    public function store(ContentEntryRequest $request): RedirectResponse
    {
        $contentEntry = ContentEntry::query()->create($request->payload());

        return redirect()
            ->route('admin.cms.content-entries.edit', $contentEntry)
            ->with('success', 'Content entry created.');
    }

    public function edit(ContentEntry $platformContentEntry): View
    {
        return view('experience-cms::admin.content-entries.form', [
            'contentEntry' => $platformContentEntry,
        ]);
    }

    public function update(ContentEntryRequest $request, ContentEntry $platformContentEntry): RedirectResponse
    {
        $platformContentEntry->update($request->payload());

        return redirect()
            ->route('admin.cms.content-entries.edit', $platformContentEntry)
            ->with('success', 'Content entry updated.');
    }

    public function destroy(ContentEntry $platformContentEntry): RedirectResponse
    {
        $platformContentEntry->delete();

        return redirect()
            ->route('admin.cms.content-entries.index')
            ->with('success', 'Content entry deleted.');
    }
}
