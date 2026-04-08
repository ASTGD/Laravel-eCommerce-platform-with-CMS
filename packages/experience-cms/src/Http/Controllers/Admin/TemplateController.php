<?php

declare(strict_types=1);

namespace ExperienceCms\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use ExperienceCms\Http\Requests\Admin\TemplateRequest;
use ExperienceCms\Models\Template;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TemplateController extends Controller
{
    public function index(): View
    {
        return view('experience-cms::admin.templates.index', [
            'templates' => Template::query()->latest()->get(),
        ]);
    }

    public function create(): View
    {
        return view('experience-cms::admin.templates.form', ['template' => new Template(['is_active' => true]), 'mode' => 'create']);
    }

    public function store(TemplateRequest $request): RedirectResponse
    {
        $template = Template::query()->create($request->validated());

        return redirect()->route('admin.templates.edit', $template)->with('status', 'Template created.');
    }

    public function edit(string $template): View
    {
        $template = Template::query()->findOrFail($template);

        return view('experience-cms::admin.templates.form', ['template' => $template, 'mode' => 'edit']);
    }

    public function update(TemplateRequest $request, string $template): RedirectResponse
    {
        $template = Template::query()->findOrFail($template);
        $template->update($request->validated());

        return redirect()->route('admin.templates.edit', $template)->with('status', 'Template updated.');
    }

    public function destroy(string $template): RedirectResponse
    {
        $template = Template::query()->findOrFail($template);
        $template->delete();

        return redirect()->route('admin.templates.index')->with('status', 'Template deleted.');
    }
}
