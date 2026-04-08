<?php

namespace Platform\ThemeCore\Services;

use Platform\ThemeCore\Contracts\ThemePresetResolverContract;
use Platform\ThemeCore\Models\ThemePreset;

class ThemePresetResolver implements ThemePresetResolverContract
{
    public function resolve(?string $code = null): ?ThemePreset
    {
        if ($code) {
            return ThemePreset::query()->where('code', $code)->first();
        }

        return ThemePreset::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->first();
    }
}
