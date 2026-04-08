<?php

namespace Platform\ExperienceCms\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Platform\ExperienceCms\Contracts\PagePreviewServiceContract;
use Platform\ExperienceCms\Contracts\PublishWorkflowContract;
use Platform\ExperienceCms\Http\Requests\Admin\PageRequest;
use Platform\ExperienceCms\Models\Page;
use Platform\ExperienceCms\Models\Template;

class PageController extends Controller
{
    public function __construct(
        protected PagePreviewServiceContract $previewService,
        protected PublishWorkflowContract $publishWorkflow,
    ) {}

    public function index(): View
    {
        return view('experience-cms::admin.pages.index', [
            'pages' => Page::query()->with('template')->orderBy('title')->get(),
        ]);
    }

    public function create(): View
    {
        return view('experience-cms::admin.pages.form', [
            'page' => new Page(['type' => 'homepage']),
            'templates' => Template::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(PageRequest $request): RedirectResponse
    {
        $page = Page::query()->create($request->payload() + [
            'status' => 'draft',
        ]);

        return redirect()
            ->route('admin.cms.pages.edit', $page)
            ->with('success', 'Page created.');
    }

    public function edit(Page $page): View
    {
        return view('experience-cms::admin.pages.form', [
            'page' => $page,
            'templates' => Template::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(PageRequest $request, Page $page): RedirectResponse
    {
        $page->update($request->payload());

        return redirect()
            ->route('admin.cms.pages.edit', $page)
            ->with('success', 'Page updated.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $page->delete();

        return redirect()
            ->route('admin.cms.pages.index')
            ->with('success', 'Page deleted.');
    }

    public function preview(Page $page): View
    {
        return view('theme-default::storefront.page', $this->previewService->build($page));
    }

    public function publish(Page $page): RedirectResponse
    {
        $this->publishWorkflow->publish($page);

        return redirect()
            ->route('admin.cms.pages.edit', $page)
            ->with('success', 'Page published.');
    }
}
