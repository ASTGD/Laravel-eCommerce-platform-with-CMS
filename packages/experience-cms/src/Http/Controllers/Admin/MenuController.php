<?php

namespace Platform\ExperienceCms\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Platform\ExperienceCms\Http\Requests\Admin\MenuRequest;
use Platform\ExperienceCms\Models\Menu;

class MenuController extends Controller
{
    public function index(): View
    {
        return view('experience-cms::admin.menus.index', [
            'menus' => Menu::query()->orderBy('location')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('experience-cms::admin.menus.form', [
            'menu' => new Menu(['is_active' => true]),
        ]);
    }

    public function store(MenuRequest $request): RedirectResponse
    {
        $menu = Menu::query()->create($request->payload());

        return redirect()
            ->route('admin.cms.menus.edit', $menu)
            ->with('success', 'Menu created.');
    }

    public function edit(Menu $menu): View
    {
        return view('experience-cms::admin.menus.form', compact('menu'));
    }

    public function update(MenuRequest $request, Menu $menu): RedirectResponse
    {
        $menu->update($request->payload());

        return redirect()
            ->route('admin.cms.menus.edit', $menu)
            ->with('success', 'Menu updated.');
    }

    public function destroy(Menu $menu): RedirectResponse
    {
        $menu->delete();

        return redirect()
            ->route('admin.cms.menus.index')
            ->with('success', 'Menu deleted.');
    }
}
