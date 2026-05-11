<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('theme_presets') || ! Schema::hasTable('theme_token_sets')) {
            return;
        }

        $now = now();

        $tokensJson = [
            'code' => 'clothing',
            'name' => 'Clothing',
            'colors' => [
                'background' => '#ffffff',
                'surface' => '#f5f7f2',
                'primary' => '#0400ffff',
                'accent' => '#00ff37ff',
                'text' => '#111111',
                'muted' => '#5f6368',
            ],
        ];

        $settingsJson = [
            'shop_theme_code' => 'clothing',
            'header_variant' => 'clothing',
            'footer_variant' => 'clothing',
            'product_card_variant' => 'clothing',
        ];

        DB::table('theme_token_sets')->updateOrInsert(
            ['code' => 'clothing'],
            [
                'name' => 'Clothing',
                'tokens_json' => json_encode($tokensJson, JSON_UNESCAPED_SLASHES),
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        DB::table('theme_presets')->updateOrInsert(
            ['code' => 'clothing'],
            [
                'name' => 'Clothing',
                'tokens_json' => json_encode($tokensJson, JSON_UNESCAPED_SLASHES),
                'settings_json' => json_encode($settingsJson, JSON_UNESCAPED_SLASHES),
                'is_default' => false,
                'is_active' => false,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );
    }

    public function down(): void
    {
        if (Schema::hasTable('theme_presets')) {
            DB::table('theme_presets')->where('code', 'clothing')->delete();
        }

        if (Schema::hasTable('theme_token_sets')) {
            DB::table('theme_token_sets')->where('code', 'clothing')->delete();
        }
    }
};
