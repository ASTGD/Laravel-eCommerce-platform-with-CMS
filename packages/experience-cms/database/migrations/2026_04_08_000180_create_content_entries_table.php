<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_entries', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('title');
            $table->string('slug')->unique();
            $table->json('body_json')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_entries');
    }
};
