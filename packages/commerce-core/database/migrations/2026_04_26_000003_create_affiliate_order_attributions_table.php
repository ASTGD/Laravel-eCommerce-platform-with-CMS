<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_order_attributions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('affiliate_profile_id')->constrained('affiliate_profiles')->cascadeOnDelete();
            $table->foreignId('affiliate_click_id')->nullable()->constrained('affiliate_clicks')->nullOnDelete();
            $table->unsignedInteger('order_id')->unique();
            $table->string('referral_code', 64)->index();
            $table->string('attribution_source', 32)->default('cookie');
            $table->string('status', 32)->index();
            $table->timestamp('attributed_at')->index();
            $table->timestamp('expires_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_order_attributions');
    }
};
