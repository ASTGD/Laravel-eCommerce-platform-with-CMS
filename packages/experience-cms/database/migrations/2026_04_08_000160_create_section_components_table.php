<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('section_components', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('page_section_id')->constrained('page_sections')->cascadeOnDelete();
            $table->foreignId('component_type_id')->constrained('component_types')->restrictOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('settings_json')->nullable();
            $table->string('data_source_type')->nullable();
            $table->json('data_source_payload_json')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('section_components');
    }
};
