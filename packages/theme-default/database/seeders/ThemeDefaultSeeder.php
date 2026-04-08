<?php

declare(strict_types=1);

namespace ThemeDefault\Database\Seeders;

use ExperienceCms\Models\ThemePreset;
use Illuminate\Database\Seeder;

class ThemeDefaultSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Modern', 'code' => 'modern', 'path' => base_path('themes/preset-modern/tokens.json'), 'is_default' => true],
            ['name' => 'Classic', 'code' => 'classic', 'path' => base_path('themes/preset-classic/tokens.json'), 'is_default' => false],
        ] as $preset) {
            $tokens = json_decode((string) file_get_contents($preset['path']), true, 512, JSON_THROW_ON_ERROR);

            ThemePreset::query()->updateOrCreate(
                ['code' => $preset['code']],
                [
                    'name' => $preset['name'],
                    'tokens_json' => $tokens,
                    'settings_json' => [
                        'header_style' => $preset['code'],
                        'footer_style' => $preset['code'],
                        'card_style' => $preset['code'],
                    ],
                    'is_default' => $preset['is_default'],
                    'is_active' => true,
                ],
            );
        }
    }
}
