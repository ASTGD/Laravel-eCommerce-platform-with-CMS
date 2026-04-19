<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('order_id');
            $table->unsignedInteger('native_shipment_id')->nullable()->unique();
            $table->foreignId('shipment_carrier_id')->nullable()->constrained('shipment_carriers')->nullOnDelete();
            $table->unsignedInteger('inventory_source_id')->nullable();
            $table->unsignedInteger('created_by_admin_id')->nullable();
            $table->unsignedInteger('updated_by_admin_id')->nullable();
            $table->string('status', 50)->index();
            $table->string('carrier_name_snapshot')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('inventory_source_name')->nullable();
            $table->string('origin_label')->nullable();
            $table->string('destination_country', 2)->nullable();
            $table->string('destination_region')->nullable();
            $table->string('destination_city')->nullable();
            $table->string('recipient_name')->nullable();
            $table->string('recipient_phone')->nullable();
            $table->text('recipient_address')->nullable();
            $table->decimal('cod_amount_expected', 12, 2)->default(0);
            $table->decimal('cod_amount_collected', 12, 2)->default(0);
            $table->decimal('carrier_fee_amount', 12, 2)->default(0);
            $table->decimal('cod_fee_amount', 12, 2)->default(0);
            $table->decimal('return_fee_amount', 12, 2)->default(0);
            $table->decimal('net_remittable_amount', 12, 2)->default(0);
            $table->timestamp('handed_over_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('native_shipment_id')->references('id')->on('shipments')->onDelete('set null');
            $table->foreign('inventory_source_id')->references('id')->on('inventory_sources')->onDelete('set null');
            $table->foreign('created_by_admin_id')->references('id')->on('admins')->onDelete('set null');
            $table->foreign('updated_by_admin_id')->references('id')->on('admins')->onDelete('set null');

            $table->index(['shipment_carrier_id', 'status']);
            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_records');
    }
};
