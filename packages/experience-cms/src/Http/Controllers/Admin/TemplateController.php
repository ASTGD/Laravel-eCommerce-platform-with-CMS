<?php

namespace Platform\ExperienceCms\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Platform\ExperienceCms\Http\Requests\Admin\TemplateRequest;
use Platform\ExperienceCms\Models\Template;

class TemplateController extends Controller
{
    public function index(): View
    {
        return view('experience-cms::admin.templates.index', [
            'templates' => Template::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('experience-cms::admin.templates.form', [
            'template' => new Template(['is_active' => true]),
        ]);
    }

    public function store(TemplateRequest $request): RedirectResponse
    {
        $template = Template::query()->create($request->payload());

        return redirect()
            ->route('admin.cms.templates.edit', $template)
            ->with('success', 'Template created.');
    }

    public function edit(Template $template): View
    {
        return view('experience-cms::admin.templates.form', compact('template'));
    }

    public function update(TemplateRequest $request, Template $template): RedirectResponse
    {
        $template->update($request->payload());

        return redirect()
            ->route('admin.cms.templates.edit', $template)
            ->with('success', 'Template updated.');
    }

    public function destroy(Template $template): RedirectResponse
    {
        $template->delete();

        return redirect()
            ->route('admin.cms.templates.index')
            ->with('success', 'Template deleted.');
    }
}
