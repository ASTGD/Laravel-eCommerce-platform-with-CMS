<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_payouts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('affiliate_profile_id')->constrained('affiliate_profiles')->cascadeOnDelete();
            $table->unsignedInteger('requested_by_customer_id')->nullable();
            $table->unsignedInteger('processed_by_admin_id')->nullable();
            $table->string('status', 32)->index();
            $table->decimal('amount', 12, 4)->default(0);
            $table->string('currency', 3)->nullable();
            $table->string('payout_method')->nullable();
            $table->string('payout_reference')->nullable()->unique();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('requested_by_customer_id')->references('id')->on('customers')->nullOnDelete();
            $table->foreign('processed_by_admin_id')->references('id')->on('admins')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_payouts');
    }
};
