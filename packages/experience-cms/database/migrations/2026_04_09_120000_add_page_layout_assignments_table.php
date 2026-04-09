<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->foreignId('header_config_id')
                ->nullable()
                ->after('template_id')
                ->constrained('header_configs')
                ->nullOnDelete();

            $table->foreignId('footer_config_id')
                ->nullable()
                ->after('header_config_id')
                ->constrained('footer_configs')
                ->nullOnDelete();

            $table->foreignId('menu_id')
                ->nullable()
                ->after('footer_config_id')
                ->constrained('menus')
                ->nullOnDelete();

            $table->foreignId('theme_preset_id')
                ->nullable()
                ->after('menu_id')
                ->constrained('theme_presets')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('theme_preset_id');
            $table->dropConstrainedForeignId('menu_id');
            $table->dropConstrainedForeignId('footer_config_id');
            $table->dropConstrainedForeignId('header_config_id');
        });
    }
};
