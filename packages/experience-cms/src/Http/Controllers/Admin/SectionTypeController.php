<?php

namespace Platform\ExperienceCms\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Platform\ExperienceCms\Http\Requests\Admin\SectionTypeRequest;
use Platform\ExperienceCms\Models\SectionType;

class SectionTypeController extends Controller
{
    public function index(): View
    {
        return view('experience-cms::admin.section-types.index', [
            'sectionTypes' => SectionType::query()->orderBy('category')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('experience-cms::admin.section-types.form', [
            'sectionType' => new SectionType(['is_active' => true]),
        ]);
    }

    public function store(SectionTypeRequest $request): RedirectResponse
    {
        $sectionType = SectionType::query()->create($request->payload());

        return redirect()
            ->route('admin.cms.section-types.edit', $sectionType)
            ->with('success', 'Section type created.');
    }

    public function edit(SectionType $platformSectionType): View
    {
        return view('experience-cms::admin.section-types.form', [
            'sectionType' => $platformSectionType,
        ]);
    }

    public function update(SectionTypeRequest $request, SectionType $platformSectionType): RedirectResponse
    {
        $platformSectionType->update($request->payload());

        return redirect()
            ->route('admin.cms.section-types.edit', $platformSectionType)
            ->with('success', 'Section type updated.');
    }

    public function destroy(SectionType $platformSectionType): RedirectResponse
    {
        $platformSectionType->delete();

        return redirect()
            ->route('admin.cms.section-types.index')
            ->with('success', 'Section type deleted.');
    }
}
