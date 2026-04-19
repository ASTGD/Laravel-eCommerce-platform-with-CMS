<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipment_records', function (Blueprint $table) {
            $table->string('carrier_booking_reference')->nullable()->after('tracking_number');
            $table->string('carrier_consignment_id')->nullable()->after('carrier_booking_reference');
            $table->string('carrier_invoice_reference')->nullable()->after('carrier_consignment_id');
            $table->timestamp('carrier_booked_at')->nullable()->after('carrier_invoice_reference');

            $table->index(['shipment_carrier_id', 'carrier_booking_reference'], 'shipment_records_carrier_booking_reference_index');
            $table->index(['shipment_carrier_id', 'carrier_consignment_id'], 'shipment_records_carrier_consignment_id_index');
            $table->index(['shipment_carrier_id', 'carrier_invoice_reference'], 'shipment_records_carrier_invoice_reference_index');
        });
    }

    public function down(): void
    {
        Schema::table('shipment_records', function (Blueprint $table) {
            $table->dropIndex('shipment_records_carrier_booking_reference_index');
            $table->dropIndex('shipment_records_carrier_consignment_id_index');
            $table->dropIndex('shipment_records_carrier_invoice_reference_index');

            $table->dropColumn([
                'carrier_booking_reference',
                'carrier_consignment_id',
                'carrier_invoice_reference',
                'carrier_booked_at',
            ]);
        });
    }
};
