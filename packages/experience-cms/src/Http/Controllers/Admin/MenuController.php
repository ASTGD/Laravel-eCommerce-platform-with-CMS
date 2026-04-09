<?php

namespace Platform\ExperienceCms\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Platform\ExperienceCms\Http\Requests\Admin\MenuRequest;
use Platform\ExperienceCms\Models\Menu;
use Platform\ExperienceCms\Services\MenuEditor;

class MenuController extends Controller
{
    public function __construct(protected MenuEditor $menuEditor) {}

    public function index(): View
    {
        return view('experience-cms::admin.menus.index', [
            'menus' => Menu::query()->withCount('items')->orderBy('location')->orderBy('name')->get(),
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
        $menu = $this->menuEditor->create($request->payload(), $request->itemsPayload());

        return redirect()
            ->route('admin.cms.menus.edit', $menu)
            ->with('success', 'Menu created.');
    }

    public function edit(Menu $platformMenu): View
    {
        return view('experience-cms::admin.menus.form', [
            'menu' => $platformMenu->load('items'),
        ]);
    }

    public function update(MenuRequest $request, Menu $platformMenu): RedirectResponse
    {
        $platformMenu = $this->menuEditor->update($platformMenu, $request->payload(), $request->itemsPayload());

        return redirect()
            ->route('admin.cms.menus.edit', $platformMenu)
            ->with('success', 'Menu updated.');
    }

    public function destroy(Menu $platformMenu): RedirectResponse
    {
        $platformMenu->delete();

        return redirect()
            ->route('admin.cms.menus.index')
            ->with('success', 'Menu deleted.');
    }
}
