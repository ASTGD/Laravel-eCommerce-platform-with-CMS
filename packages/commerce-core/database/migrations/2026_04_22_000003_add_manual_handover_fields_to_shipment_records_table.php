<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipment_records', function (Blueprint $table) {
            $table->foreignId('handover_batch_id')->nullable()->after('shipment_carrier_id')->constrained('shipment_handover_batches')->nullOnDelete();
            $table->boolean('stock_checked')->default(false)->after('tracking_number');
            $table->unsignedInteger('packed_by_admin_id')->nullable()->after('updated_by_admin_id');
            $table->timestamp('packed_at')->nullable()->after('stock_checked');
            $table->unsignedInteger('package_count')->default(1)->after('packed_at');
            $table->decimal('package_weight_kg', 8, 2)->nullable()->after('package_count');
            $table->string('package_dimensions')->nullable()->after('package_weight_kg');
            $table->boolean('is_fragile')->default(false)->after('package_dimensions');
            $table->string('handover_mode', 50)->nullable()->after('is_fragile');
            $table->text('special_handling')->nullable()->after('handover_mode');
            $table->text('internal_note')->nullable()->after('special_handling');
            $table->text('courier_note')->nullable()->after('internal_note');

            $table->foreign('packed_by_admin_id')->references('id')->on('admins')->nullOnDelete();
            $table->index(['status', 'handover_mode'], 'shipment_records_status_handover_mode_index');
            $table->index(['shipment_carrier_id', 'packed_at'], 'shipment_records_carrier_packed_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('shipment_records', function (Blueprint $table) {
            $table->dropIndex('shipment_records_status_handover_mode_index');
            $table->dropIndex('shipment_records_carrier_packed_at_index');
            $table->dropForeign(['handover_batch_id']);
            $table->dropForeign(['packed_by_admin_id']);
            $table->dropColumn([
                'handover_batch_id',
                'stock_checked',
                'packed_by_admin_id',
                'packed_at',
                'package_count',
                'package_weight_kg',
                'package_dimensions',
                'is_fragile',
                'handover_mode',
                'special_handling',
                'internal_note',
                'courier_note',
            ]);
        });
    }
};
