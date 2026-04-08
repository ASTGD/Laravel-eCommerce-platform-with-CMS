<?php

declare(strict_types=1);

namespace ExperienceCms\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use ExperienceCms\Http\Requests\Admin\PageSectionRequest;
use ExperienceCms\Models\Page;
use ExperienceCms\Models\PageSection;
use ExperienceCms\Models\SectionType;
use ExperienceCms\Services\SectionRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageSectionController extends Controller
{
    public function create(Request $request, string $page): View
    {
        $page = Page::query()->findOrFail($page);
        $sectionRegistry = app(SectionRegistry::class);
        $sectionTypes = SectionType::query()->where('is_active', true)->orderBy('name')->get();
        $selectedType = SectionType::query()->find($request->integer('section_type_id')) ?? $sectionTypes->first();
        $definition = $selectedType !== null ? $sectionRegistry->find($selectedType->code) : null;

        return view('experience-cms::admin.page-sections.form', [
            'page' => $page,
            'section' => new PageSection(['sort_order' => (int) $page->sections()->max('sort_order') + 1, 'is_active' => true]),
            'sectionTypes' => $sectionTypes,
            'selectedType' => $selectedType,
            'definition' => $definition,
            'mode' => 'create',
        ]);
    }

    public function store(PageSectionRequest $request, string $page): RedirectResponse
    {
        $page = Page::query()->findOrFail($page);
        $page->sections()->create($request->validated());

        return redirect()->route('admin.pages.edit', $page)->with('status', 'Section added.');
    }

    public function edit(string $page, string $section): View
    {
        $page = Page::query()->findOrFail($page);
        $section = PageSection::query()->with('sectionType')->findOrFail($section);
        $sectionRegistry = app(SectionRegistry::class);
        $sectionTypes = SectionType::query()->where('is_active', true)->orderBy('name')->get();
        $selectedType = $section->sectionType;
        $definition = $selectedType !== null ? $sectionRegistry->find($selectedType->code) : null;

        return view('experience-cms::admin.page-sections.form', [
            'page' => $page,
            'section' => $section,
            'sectionTypes' => $sectionTypes,
            'selectedType' => $selectedType,
            'definition' => $definition,
            'mode' => 'edit',
        ]);
    }

    public function update(PageSectionRequest $request, string $page, string $section): RedirectResponse
    {
        $page = Page::query()->findOrFail($page);
        $section = PageSection::query()->findOrFail($section);
        $section->update($request->validated());

        return redirect()->route('admin.pages.edit', $page)->with('status', 'Section updated.');
    }

    public function destroy(string $page, string $section): RedirectResponse
    {
        $page = Page::query()->findOrFail($page);
        $section = PageSection::query()->findOrFail($section);
        $section->delete();

        return redirect()->route('admin.pages.edit', $page)->with('status', 'Section deleted.');
    }
}
