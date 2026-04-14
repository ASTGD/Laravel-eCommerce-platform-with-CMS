<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_attempts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('cart_id')->nullable();
            $table->unsignedInteger('order_id')->nullable();
            $table->unsignedInteger('customer_id')->nullable();
            $table->string('provider');
            $table->string('method_code');
            $table->string('merchant_tran_id');
            $table->string('session_key')->nullable()->unique();
            $table->string('gateway_tran_id')->nullable();
            $table->string('currency', 8);
            $table->decimal('amount', 12, 4)->default(0);
            $table->string('status')->default('initiated');
            $table->string('validation_status')->nullable();
            $table->string('finalized_via')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamp('last_callback_at')->nullable();
            $table->timestamp('last_ipn_at')->nullable();
            $table->unsignedInteger('callback_count')->default(0);
            $table->unsignedInteger('ipn_count')->default(0);
            $table->json('meta')->nullable();
            $table->json('last_payload')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'merchant_tran_id'], 'payment_attempts_provider_merchant_unique');
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
            $table->index(['provider', 'method_code']);
            $table->index(['cart_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_attempts');
    }
};
