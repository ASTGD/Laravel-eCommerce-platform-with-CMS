<?php

namespace Platform\ThemeCore\Database\Seeders;

use Illuminate\Database\Seeder;
use Platform\ThemeCore\Models\ThemePreset;
use Platform\ThemeCore\Models\ThemeTokenSet;

class ThemeCoreSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['modern', 'minimal', 'classic'] as $index => $code) {
            $tokens = json_decode((string) file_get_contents(base_path("themes/preset-{$code}/tokens.json")), true);

            ThemeTokenSet::query()->updateOrCreate(
                ['code' => $tokens['code']],
                [
                    'name'       => $tokens['name'],
                    'tokens_json' => $tokens,
                ]
            );

            ThemePreset::query()->updateOrCreate(
                ['code' => $tokens['code']],
                [
                    'name'          => $tokens['name'],
                    'tokens_json'   => $tokens,
                    'settings_json' => [
                        'header_variant' => 'standard',
                        'footer_variant' => 'standard',
                        'product_card_variant' => $code,
                    ],
                    'is_default' => $index === 0,
                    'is_active'  => true,
                ]
            );
        }
    }
}
