<?php

namespace Platform\ExperienceCms\Services;

use Illuminate\Support\Facades\DB;
use Platform\ExperienceCms\Models\Menu;
use Platform\ExperienceCms\Models\MenuItem;

class MenuEditor
{
    public function create(array $attributes, array $items = []): Menu
    {
        return $this->persist(new Menu, $attributes, $items);
    }

    public function update(Menu $menu, array $attributes, array $items = []): Menu
    {
        return $this->persist($menu, $attributes, $items);
    }

    protected function persist(Menu $menu, array $attributes, array $items): Menu
    {
        return DB::transaction(function () use ($menu, $attributes, $items) {
            $menu->fill($attributes)->save();

            $existing = $menu->items()->get()->keyBy('id');
            $seenIds = [];

            foreach ($items as $itemData) {
                $item = $itemData['id']
                    ? $existing->get($itemData['id'], new MenuItem(['menu_id' => $menu->getKey()]))
                    : new MenuItem(['menu_id' => $menu->getKey()]);

                $item->fill([
                    'title' => $itemData['title'],
                    'type' => $itemData['type'],
                    'target' => $itemData['target'],
                    'sort_order' => $itemData['sort_order'],
                    'settings_json' => [],
                    'is_active' => $itemData['is_active'],
                    'parent_id' => null,
                ])->save();

                $seenIds[] = $item->getKey();
            }

            $menu->items()
                ->when($seenIds !== [], fn ($query) => $query->whereNotIn('id', $seenIds), fn ($query) => $query)
                ->delete();

            return $menu->fresh('items.children');
        });
    }
}
