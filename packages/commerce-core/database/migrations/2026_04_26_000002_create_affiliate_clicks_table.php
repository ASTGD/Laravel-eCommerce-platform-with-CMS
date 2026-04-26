<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_clicks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('affiliate_profile_id')->constrained('affiliate_profiles')->cascadeOnDelete();
            $table->unsignedInteger('customer_id')->nullable();
            $table->string('referral_code', 64)->index();
            $table->string('session_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('landing_url')->nullable();
            $table->text('referrer_url')->nullable();
            $table->timestamp('clicked_at')->index();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_clicks');
    }
};
