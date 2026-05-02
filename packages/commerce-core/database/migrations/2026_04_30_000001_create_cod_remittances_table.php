<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cod_remittances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shipment_carrier_id')->nullable();
            $table->unsignedInteger('created_by_admin_id')->nullable();
            $table->string('reference')->unique();
            $table->string('status', 50)->default('allocated');
            $table->decimal('amount_received', 12, 2)->default(0);
            $table->decimal('allocated_amount', 12, 2)->default(0);
            $table->decimal('unallocated_amount', 12, 2)->default(0);
            $table->timestamp('received_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('shipment_carrier_id')->references('id')->on('shipment_carriers')->nullOnDelete();
            $table->foreign('created_by_admin_id')->references('id')->on('admins')->nullOnDelete();

            $table->index(['shipment_carrier_id']);
            $table->index(['status']);
            $table->index(['received_at']);
        });

        Schema::create('cod_remittance_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cod_remittance_id')->constrained('cod_remittances')->cascadeOnDelete();
            $table->unsignedBigInteger('cod_settlement_id');
            $table->unsignedInteger('order_id');
            $table->unsignedBigInteger('shipment_record_id')->nullable();
            $table->decimal('allocated_amount', 12, 2)->default(0);
            $table->string('status', 50)->default('allocated');
            $table->timestamps();

            $table->foreign('cod_settlement_id')->references('id')->on('cod_settlements')->cascadeOnDelete();
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('shipment_record_id')->references('id')->on('shipment_records')->nullOnDelete();

            $table->index(['cod_remittance_id']);
            $table->index(['cod_settlement_id']);
            $table->index(['order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cod_remittance_allocations');
        Schema::dropIfExists('cod_remittances');
    }
};
