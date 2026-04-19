<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settlement_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shipment_carrier_id')->nullable();
            $table->unsignedInteger('created_by_admin_id')->nullable();
            $table->unsignedInteger('updated_by_admin_id')->nullable();
            $table->string('reference')->unique();
            $table->string('payout_method')->nullable();
            $table->string('status', 50)->default('draft');
            $table->decimal('gross_expected_amount', 12, 2)->default(0);
            $table->decimal('gross_remitted_amount', 12, 2)->default(0);
            $table->decimal('total_adjustment_amount', 12, 2)->default(0);
            $table->decimal('total_short_amount', 12, 2)->default(0);
            $table->decimal('total_deductions_amount', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2)->default(0);
            $table->timestamp('remitted_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('shipment_carrier_id')->references('id')->on('shipment_carriers')->onDelete('set null');
            $table->foreign('created_by_admin_id')->references('id')->on('admins')->nullOnDelete();
            $table->foreign('updated_by_admin_id')->references('id')->on('admins')->nullOnDelete();

            $table->index(['status']);
            $table->index(['shipment_carrier_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settlement_batches');
    }
};
