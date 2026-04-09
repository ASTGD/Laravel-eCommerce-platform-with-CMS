<?php

namespace Platform\ExperienceCms\Services;

use Platform\ExperienceCms\Contracts\MenuResolverContract;
use Platform\ExperienceCms\Models\Menu;

class MenuResolver implements MenuResolverContract
{
    public function resolve(?string $code = null): ?Menu
    {
        $query = Menu::query()
            ->where('is_active', true)
            ->with([
                'items' => fn ($itemQuery) => $itemQuery
                    ->where('is_active', true)
                    ->orderBy('sort_order'),
                'items.children' => fn ($itemQuery) => $itemQuery
                    ->where('is_active', true)
                    ->orderBy('sort_order'),
            ]);

        if ($code) {
            return $query->where('code', $code)->first();
        }

        return $query->orderBy('location')->first();
    }
}
