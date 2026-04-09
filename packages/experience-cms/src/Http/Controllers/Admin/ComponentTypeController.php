<?php

namespace Platform\ExperienceCms\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Platform\ExperienceCms\Http\Requests\Admin\ComponentTypeRequest;
use Platform\ExperienceCms\Models\ComponentType;

class ComponentTypeController extends Controller
{
    public function index(): View
    {
        return view('experience-cms::admin.component-types.index', [
            'componentTypes' => ComponentType::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('experience-cms::admin.component-types.form', [
            'componentType' => new ComponentType(['is_active' => true]),
        ]);
    }

    public function store(ComponentTypeRequest $request): RedirectResponse
    {
        $componentType = ComponentType::query()->create($request->payload());

        return redirect()
            ->route('admin.cms.component-types.edit', $componentType)
            ->with('success', 'Component type created.');
    }

    public function edit(ComponentType $platformComponentType): View
    {
        return view('experience-cms::admin.component-types.form', [
            'componentType' => $platformComponentType,
        ]);
    }

    public function update(ComponentTypeRequest $request, ComponentType $platformComponentType): RedirectResponse
    {
        $platformComponentType->update($request->payload());

        return redirect()
            ->route('admin.cms.component-types.edit', $platformComponentType)
            ->with('success', 'Component type updated.');
    }

    public function destroy(ComponentType $platformComponentType): RedirectResponse
    {
        $platformComponentType->delete();

        return redirect()
            ->route('admin.cms.component-types.index')
            ->with('success', 'Component type deleted.');
    }
}
