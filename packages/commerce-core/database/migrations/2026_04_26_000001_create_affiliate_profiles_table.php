<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_profiles', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('customer_id')->unique();
            $table->string('status', 32)->index();
            $table->string('referral_code', 64)->unique();
            $table->string('application_source')->nullable();
            $table->text('application_note')->nullable();
            $table->string('website_url')->nullable();
            $table->json('social_profiles')->nullable();
            $table->string('payout_method')->nullable();
            $table->string('payout_reference')->nullable();
            $table->timestamp('terms_accepted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedInteger('approved_by_admin_id')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->unsignedInteger('rejected_by_admin_id')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->unsignedInteger('suspended_by_admin_id')->nullable();
            $table->text('suspension_reason')->nullable();
            $table->timestamp('reactivated_at')->nullable();
            $table->timestamp('last_status_changed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->foreign('approved_by_admin_id')->references('id')->on('admins')->nullOnDelete();
            $table->foreign('rejected_by_admin_id')->references('id')->on('admins')->nullOnDelete();
            $table->foreign('suspended_by_admin_id')->references('id')->on('admins')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_profiles');
    }
};
