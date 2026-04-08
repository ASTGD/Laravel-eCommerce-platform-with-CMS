<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('templates')->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->json('rules_json')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['template_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_areas');
    }
};
