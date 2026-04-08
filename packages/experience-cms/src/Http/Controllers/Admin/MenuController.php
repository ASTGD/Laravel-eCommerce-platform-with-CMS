<?php

declare(strict_types=1);

namespace ExperienceCms\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use ExperienceCms\Http\Requests\Admin\MenuRequest;
use ExperienceCms\Models\Menu;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function index(): View
    {
        return view('experience-cms::admin.menus.index', [
            'menus' => Menu::query()->withCount('items')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('experience-cms::admin.menus.form', ['menu' => new Menu(['is_active' => true]), 'mode' => 'create']);
    }

    public function store(MenuRequest $request): RedirectResponse
    {
        $menu = Menu::query()->create($request->validated());

        return redirect()->route('admin.menus.edit', $menu)->with('status', 'Menu created.');
    }

    public function edit(string $menu): View
    {
        $menu = Menu::query()->findOrFail($menu);
        $menu->load(['items' => fn ($query) => $query->whereNull('parent_id')->with('children')->orderBy('sort_order')]);

        return view('experience-cms::admin.menus.form', ['menu' => $menu, 'mode' => 'edit']);
    }

    public function update(MenuRequest $request, string $menu): RedirectResponse
    {
        $menu = Menu::query()->findOrFail($menu);
        $menu->update($request->validated());

        return redirect()->route('admin.menus.edit', $menu)->with('status', 'Menu updated.');
    }

    public function destroy(string $menu): RedirectResponse
    {
        $menu = Menu::query()->findOrFail($menu);
        $menu->delete();

        return redirect()->route('admin.menus.index')->with('status', 'Menu deleted.');
    }
}
