<?php

namespace Platform\ThemeCore\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Platform\ThemeCore\Models\ThemePreset;
use Platform\ThemeCore\Models\ThemeTokenSet;

class ThemeCoreSeeder extends Seeder
{
    public function run(): void
    {
        $this->renameLegacyDefaultPreset();
        $this->pruneLegacyPlaceholderPresets();

        foreach ($this->presets() as $index => $preset) {
            ThemeTokenSet::query()->updateOrCreate(
                ['code' => $preset['code']],
                [
                    'name' => $preset['name'],
                    'tokens_json' => $preset['tokens_json'],
                ]
            );

            ThemePreset::query()->updateOrCreate(
                ['code' => $preset['code']],
                [
                    'name' => $preset['name'],
                    'tokens_json' => $preset['tokens_json'],
                    'settings_json' => $preset['settings_json'],
                    'is_default' => $index === 0,
                    'is_active' => $index === 0,
                ]
            );
        }
    }

    protected function renameLegacyDefaultPreset(): void
    {
        $legacyPreset = ThemePreset::query()
            ->where('code', 'bagisto_native')
            ->first();

        if (! $legacyPreset) {
            return;
        }

        $defaultPreset = ThemePreset::query()
            ->where('code', 'default')
            ->first();

        if ($defaultPreset) {
            if (Schema::hasTable('pages') && Schema::hasColumn('pages', 'theme_preset_id')) {
                DB::table('pages')
                    ->where('theme_preset_id', $legacyPreset->getKey())
                    ->update(['theme_preset_id' => $defaultPreset->getKey()]);
            }

            $legacyPreset->delete();

            ThemeTokenSet::query()
                ->where('code', 'bagisto_native')
                ->delete();

            return;
        }

        $legacyPreset->update([
            'name' => 'Default',
            'code' => 'default',
        ]);

        $legacyToken = ThemeTokenSet::query()
            ->where('code', 'bagisto_native')
            ->first();

        if (! $legacyToken) {
            return;
        }

        if (ThemeTokenSet::query()->where('code', 'default')->exists()) {
            $legacyToken->delete();

            return;
        }

        $legacyToken->update([
            'name' => 'Default',
            'code' => 'default',
        ]);
    }

    protected function pruneLegacyPlaceholderPresets(): void
    {
        $registeredShopThemes = array_keys(config('themes.shop', []));

        $legacyCodes = ThemePreset::query()
            ->whereIn('code', ['classic', 'minimal', 'modern'])
            ->get()
            ->filter(function (ThemePreset $preset) use ($registeredShopThemes): bool {
                $shopThemeCode = data_get($preset->settings_json, 'shop_theme_code');

                if (in_array((string) $preset->code, $registeredShopThemes, true)) {
                    return false;
                }

                return ! is_string($shopThemeCode)
                    || $shopThemeCode === ''
                    || $shopThemeCode === $preset->code
                    || ! in_array($shopThemeCode, $registeredShopThemes, true);
            })
            ->pluck('code')
            ->all();

        if (empty($legacyCodes)) {
            return;
        }

        ThemePreset::query()
            ->whereIn('code', $legacyCodes)
            ->delete();

        ThemeTokenSet::query()
            ->whereIn('code', $legacyCodes)
            ->delete();
    }

    protected function presets(): array
    {
        return [
            [
                'name' => 'Default',
                'code' => 'default',
                'tokens_json' => [
                    'code' => 'default',
                    'name' => 'Default',
                    'colors' => [
                        'background' => '#ffffff',
                        'surface' => '#ffffff',
                        'primary' => '#060c3b',
                        'accent' => '#f97316',
                        'text' => '#111827',
                        'muted' => '#6b7280',
                    ],
                ],
                'settings_json' => [
                    'shop_theme_code' => 'default',
                    'header_variant' => 'default',
                    'footer_variant' => 'default',
                    'product_card_variant' => 'default',
                ],
            ],
            [
                'name' => 'Gadget',
                'code' => 'gadget',
                'tokens_json' => [
                    'code' => 'gadget',
                    'name' => 'Gadget',
                    'colors' => [
                        'background' => '#ffffff',
                        'surface' => '#f5f7f2',
                        'primary' => '#111111',
                        'accent' => '#ff4b37',
                        'text' => '#111111',
                        'muted' => '#5f6368',
                    ],
                ],
                'settings_json' => [
                    'shop_theme_code' => 'gadget',
                    'header_variant' => 'gadget',
                    'footer_variant' => 'gadget',
                    'product_card_variant' => 'gadget',
                ],
            ],
        ];
    }
}
