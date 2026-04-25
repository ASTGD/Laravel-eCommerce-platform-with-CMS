<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipment_records', function (Blueprint $table) {
            $table->string('label_generated_hash', 64)->nullable()->after('public_tracking_url');
            $table->timestamp('label_generated_at')->nullable()->after('label_generated_hash');
            $table->string('invoice_generated_hash', 64)->nullable()->after('label_generated_at');
            $table->timestamp('invoice_generated_at')->nullable()->after('invoice_generated_hash');

            $table->index(['order_id', 'status', 'label_generated_hash'], 'shipment_records_basic_label_gate_index');
        });
    }

    public function down(): void
    {
        Schema::table('shipment_records', function (Blueprint $table) {
            $table->dropIndex('shipment_records_basic_label_gate_index');
            $table->dropColumn([
                'label_generated_hash',
                'label_generated_at',
                'invoice_generated_hash',
                'invoice_generated_at',
            ]);
        });
    }
};
