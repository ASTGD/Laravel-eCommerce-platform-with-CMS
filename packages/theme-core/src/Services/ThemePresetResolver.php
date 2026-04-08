<?php

declare(strict_types=1);

namespace ThemeCore\Services;

use ExperienceCms\Models\SiteSetting;
use ExperienceCms\Models\ThemePreset;
use ThemeCore\Contracts\ThemePresetResolverContract;

class ThemePresetResolver implements ThemePresetResolverContract
{
    public function resolve(?string $code = null): ThemePreset
    {
        $resolvedCode = $code ?? SiteSetting::valueFor('theme.active_preset_code');

        if (is_string($resolvedCode) && $resolvedCode !== '') {
            $preset = ThemePreset::query()->where('code', $resolvedCode)->first();

            if ($preset instanceof ThemePreset) {
                return $preset;
            }
        }

        $preset = ThemePreset::query()->where('is_default', true)->first();

        if ($preset instanceof ThemePreset) {
            return $preset;
        }

        return new ThemePreset([
            'name' => 'Fallback',
            'code' => 'fallback',
            'tokens_json' => [
                'colors' => [
                    'background' => '#f7f5ef',
                    'surface' => '#fffdf8',
                    'text' => '#1f2933',
                    'muted' => '#52606d',
                    'accent' => '#9f4f2b',
                    'accent_contrast' => '#fff8f1',
                    'border' => '#dbc9b5',
                ],
                'radius' => [
                    'card' => '24px',
                    'button' => '999px',
                ],
            ],
            'settings_json' => [],
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    public function tokenMap(?string $code = null): array
    {
        return $this->resolve($code)->tokens_json ?? [];
    }
}
