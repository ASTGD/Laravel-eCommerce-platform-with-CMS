<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cod_settlements')) {
            Schema::drop('cod_settlements');
        }

        Schema::create('cod_settlements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shipment_record_id')->unique();
            $table->unsignedInteger('order_id');
            $table->unsignedBigInteger('shipment_carrier_id')->nullable();
            $table->unsignedInteger('created_by_admin_id')->nullable();
            $table->unsignedInteger('updated_by_admin_id')->nullable();
            $table->string('status', 50)->default('expected');
            $table->decimal('expected_amount', 12, 2)->default(0);
            $table->decimal('collected_amount', 12, 2)->default(0);
            $table->decimal('remitted_amount', 12, 2)->default(0);
            $table->decimal('short_amount', 12, 2)->default(0);
            $table->decimal('disputed_amount', 12, 2)->default(0);
            $table->decimal('carrier_fee_amount', 12, 2)->default(0);
            $table->decimal('cod_fee_amount', 12, 2)->default(0);
            $table->decimal('return_fee_amount', 12, 2)->default(0);
            $table->decimal('net_amount', 12, 2)->default(0);
            $table->timestamp('collected_at')->nullable();
            $table->timestamp('remitted_at')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->text('dispute_note')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('shipment_record_id')->references('id')->on('shipment_records')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('shipment_carrier_id')->references('id')->on('shipment_carriers')->onDelete('set null');
            $table->foreign('created_by_admin_id')->references('id')->on('admins')->nullOnDelete();
            $table->foreign('updated_by_admin_id')->references('id')->on('admins')->nullOnDelete();

            $table->index(['status']);
            $table->index(['order_id']);
            $table->index(['shipment_carrier_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cod_settlements');
    }
};
