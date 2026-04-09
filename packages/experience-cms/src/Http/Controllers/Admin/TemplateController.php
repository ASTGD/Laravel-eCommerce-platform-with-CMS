<?php

namespace Platform\ExperienceCms\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Platform\ExperienceCms\Http\Requests\Admin\TemplateRequest;
use Platform\ExperienceCms\Models\Template;
use Platform\ExperienceCms\Services\TemplateSchemaSynchronizer;

class TemplateController extends Controller
{
    public function __construct(protected TemplateSchemaSynchronizer $schemaSynchronizer) {}

    public function index(): View
    {
        return view('experience-cms::admin.templates.index', [
            'templates' => Template::query()->withCount('areas')->orderBy('name')->get(),
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
        $this->schemaSynchronizer->sync($template, $request->areasPayload());

        return redirect()
            ->route('admin.cms.templates.edit', $template)
            ->with('success', 'Template created.');
    }

    public function edit(Template $platformTemplate): View
    {
        return view('experience-cms::admin.templates.form', [
            'template' => $platformTemplate->load('areas'),
        ]);
    }

    public function update(TemplateRequest $request, Template $platformTemplate): RedirectResponse
    {
        $platformTemplate->update($request->payload());
        $this->schemaSynchronizer->sync($platformTemplate, $request->areasPayload());

        return redirect()
            ->route('admin.cms.templates.edit', $platformTemplate)
            ->with('success', 'Template updated.');
    }

    public function destroy(Template $platformTemplate): RedirectResponse
    {
        $platformTemplate->delete();

        return redirect()
            ->route('admin.cms.templates.index')
            ->with('success', 'Template deleted.');
    }
}
