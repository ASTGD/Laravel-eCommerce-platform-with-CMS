<?php

declare(strict_types=1);

namespace ExperienceCms\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use ExperienceCms\Actions\PublishPageAction;
use ExperienceCms\Enums\PageStatus;
use ExperienceCms\Http\Requests\Admin\PageRequest;
use ExperienceCms\Models\Page;
use ExperienceCms\Models\Template;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PageController extends Controller
{
    public function index(): View
    {
        return view('experience-cms::admin.pages.index', [
            'pages' => Page::query()->with('template')->latest()->get(),
        ]);
    }

    public function create(): View
    {
        return view('experience-cms::admin.pages.form', [
            'page' => new Page(['status' => PageStatus::Draft]),
            'templates' => Template::query()->where('is_active', true)->orderBy('name')->get(),
            'mode' => 'create',
        ]);
    }

    public function store(PageRequest $request): RedirectResponse
    {
        $page = Page::query()->create([
            ...$request->validated(),
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        return redirect()->route('admin.pages.edit', $page)->with('status', 'Page created.');
    }

    public function edit(string $page): View
    {
        $page = Page::query()->with('template', 'sections.sectionType')->findOrFail($page);
        $page->load('template', 'sections.sectionType');

        return view('experience-cms::admin.pages.form', [
            'page' => $page,
            'templates' => Template::query()->where('is_active', true)->orderBy('name')->get(),
            'mode' => 'edit',
        ]);
    }

    public function update(PageRequest $request, string $page): RedirectResponse
    {
        $page = Page::query()->findOrFail($page);
        $page->update([
            ...$request->validated(),
            'updated_by' => $request->user()?->id,
        ]);

        return redirect()->route('admin.pages.edit', $page)->with('status', 'Page updated.');
    }

    public function destroy(string $page): RedirectResponse
    {
        $page = Page::query()->findOrFail($page);
        $page->delete();

        return redirect()->route('admin.pages.index')->with('status', 'Page deleted.');
    }

    public function publish(string $page): RedirectResponse
    {
        $page = Page::query()->findOrFail($page);
        $publishPageAction = app(PublishPageAction::class);
        $publishPageAction->execute($page, auth()->id(), 'Published from admin');

        return redirect()->route('admin.pages.edit', $page)->with('status', 'Page published.');
    }

    public function unpublish(string $page): RedirectResponse
    {
        $page = Page::query()->findOrFail($page);
        $page->update([
            'status' => PageStatus::Unpublished,
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('admin.pages.edit', $page)->with('status', 'Page unpublished.');
    }
}
