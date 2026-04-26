<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_payout_commission_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('affiliate_payout_id');
            $table->foreignId('affiliate_commission_id');
            $table->foreignId('affiliate_profile_id');
            $table->string('status', 32)->index();
            $table->decimal('amount', 12, 4);
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            $table->index(['affiliate_profile_id', 'status'], 'affiliate_payout_allocations_profile_status_index');
            $table->index(['affiliate_commission_id', 'status'], 'affiliate_payout_allocations_commission_status_index');

            $table->foreign('affiliate_payout_id', 'affiliate_allocations_payout_fk')->references('id')->on('affiliate_payouts')->cascadeOnDelete();
            $table->foreign('affiliate_commission_id', 'affiliate_allocations_commission_fk')->references('id')->on('affiliate_commissions')->cascadeOnDelete();
            $table->foreign('affiliate_profile_id', 'affiliate_allocations_profile_fk')->references('id')->on('affiliate_profiles')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_payout_commission_allocations');
    }
};
