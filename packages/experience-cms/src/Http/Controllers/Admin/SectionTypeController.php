<?php

declare(strict_types=1);

namespace ExperienceCms\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use ExperienceCms\Http\Requests\Admin\SectionTypeRequest;
use ExperienceCms\Models\SectionType;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SectionTypeController extends Controller
{
    public function index(): View
    {
        return view('experience-cms::admin.section-types.index', [
            'sectionTypes' => SectionType::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('experience-cms::admin.section-types.form', ['sectionType' => new SectionType(['is_active' => true]), 'mode' => 'create']);
    }

    public function store(SectionTypeRequest $request): RedirectResponse
    {
        $sectionType = SectionType::query()->create($request->validated());

        return redirect()->route('admin.section-types.edit', $sectionType)->with('status', 'Section type created.');
    }

    public function edit(string $section_type): View
    {
        $sectionType = SectionType::query()->findOrFail($section_type);

        return view('experience-cms::admin.section-types.form', ['sectionType' => $sectionType, 'mode' => 'edit']);
    }

    public function update(SectionTypeRequest $request, string $section_type): RedirectResponse
    {
        $sectionType = SectionType::query()->findOrFail($section_type);
        $sectionType->update($request->validated());

        return redirect()->route('admin.section-types.edit', $sectionType)->with('status', 'Section type updated.');
    }

    public function destroy(string $section_type): RedirectResponse
    {
        $sectionType = SectionType::query()->findOrFail($section_type);
        $sectionType->delete();

        return redirect()->route('admin.section-types.index')->with('status', 'Section type deleted.');
    }
}
