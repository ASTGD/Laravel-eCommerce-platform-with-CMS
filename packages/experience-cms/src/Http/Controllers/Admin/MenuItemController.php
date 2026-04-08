<?php

declare(strict_types=1);

namespace ExperienceCms\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use ExperienceCms\Http\Requests\Admin\MenuItemRequest;
use ExperienceCms\Models\Menu;
use ExperienceCms\Models\MenuItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MenuItemController extends Controller
{
    public function create(string $menu): View
    {
        $menu = Menu::query()->findOrFail($menu);

        return view('experience-cms::admin.menu-items.form', [
            'menu' => $menu->load('items'),
            'item' => new MenuItem(['is_active' => true, 'sort_order' => (int) $menu->items()->max('sort_order') + 1]),
            'mode' => 'create',
        ]);
    }

    public function store(MenuItemRequest $request, string $menu): RedirectResponse
    {
        $menu = Menu::query()->findOrFail($menu);
        $menu->items()->create($request->validated());

        return redirect()->route('admin.menus.edit', $menu)->with('status', 'Menu item created.');
    }

    public function edit(string $menu, string $item): View
    {
        $menu = Menu::query()->findOrFail($menu);
        $item = MenuItem::query()->findOrFail($item);

        return view('experience-cms::admin.menu-items.form', [
            'menu' => $menu->load('items'),
            'item' => $item,
            'mode' => 'edit',
        ]);
    }

    public function update(MenuItemRequest $request, string $menu, string $item): RedirectResponse
    {
        $menu = Menu::query()->findOrFail($menu);
        $item = MenuItem::query()->findOrFail($item);
        $item->update($request->validated());

        return redirect()->route('admin.menus.edit', $menu)->with('status', 'Menu item updated.');
    }

    public function destroy(string $menu, string $item): RedirectResponse
    {
        $menu = Menu::query()->findOrFail($menu);
        $item = MenuItem::query()->findOrFail($item);
        $item->delete();

        return redirect()->route('admin.menus.edit', $menu)->with('status', 'Menu item deleted.');
    }
}
