<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_commissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('affiliate_profile_id')->constrained('affiliate_profiles')->cascadeOnDelete();
            $table->foreignId('affiliate_order_attribution_id')->nullable()->constrained('affiliate_order_attributions')->nullOnDelete();
            $table->foreignId('affiliate_payout_id')->nullable()->constrained('affiliate_payouts')->nullOnDelete();
            $table->unsignedInteger('order_id')->unique();
            $table->string('status', 32)->index();
            $table->string('commission_type', 32);
            $table->decimal('commission_rate', 12, 4)->default(0);
            $table->decimal('order_amount', 12, 4)->default(0);
            $table->decimal('commission_amount', 12, 4)->default(0);
            $table->string('currency', 3)->nullable();
            $table->timestamp('eligible_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_commissions');
    }
};
