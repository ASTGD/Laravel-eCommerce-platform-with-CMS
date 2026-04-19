<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_record_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_record_id')->constrained('shipment_records')->cascadeOnDelete();
            $table->unsignedInteger('order_item_id');
            $table->unsignedInteger('native_shipment_item_id')->nullable()->unique();
            $table->string('name')->nullable();
            $table->string('sku')->nullable();
            $table->decimal('qty', 12, 4)->default(0);
            $table->decimal('weight', 12, 4)->default(0);
            $table->timestamps();

            $table->foreign('order_item_id')->references('id')->on('order_items')->onDelete('cascade');
            $table->foreign('native_shipment_item_id')->references('id')->on('shipment_items')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_record_items');
    }
};
