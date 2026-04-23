<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_handover_batches', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('shipment_carrier_id')->nullable()->constrained('shipment_carriers')->nullOnDelete();
            $table->unsignedInteger('created_by_admin_id')->nullable();
            $table->unsignedInteger('updated_by_admin_id')->nullable();
            $table->string('handover_type', 50);
            $table->timestamp('handover_at');
            $table->unsignedInteger('parcel_count')->default(0);
            $table->decimal('total_cod_amount', 12, 2)->default(0);
            $table->string('receiver_name')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->foreign('created_by_admin_id')->references('id')->on('admins')->nullOnDelete();
            $table->foreign('updated_by_admin_id')->references('id')->on('admins')->nullOnDelete();

            $table->index(['shipment_carrier_id', 'handover_type'], 'sh_handover_batches_carrier_type_idx');
            $table->index('confirmed_at', 'sh_handover_batches_confirmed_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_handover_batches');
    }
};
