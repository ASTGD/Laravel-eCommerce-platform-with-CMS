<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipment_records', function (Blueprint $table) {
            $table->unsignedInteger('delivery_attempt_count')->default(0)->after('net_remittable_amount');
            $table->string('delivery_failure_reason', 100)->nullable()->after('delivery_attempt_count');
            $table->boolean('requires_reattempt')->default(false)->after('delivery_failure_reason');
            $table->timestamp('last_delivery_attempt_at')->nullable()->after('requires_reattempt');
            $table->timestamp('return_initiated_at')->nullable()->after('last_delivery_attempt_at');

            $table->index(['requires_reattempt']);
            $table->index(['delivery_failure_reason']);
        });
    }

    public function down(): void
    {
        Schema::table('shipment_records', function (Blueprint $table) {
            $table->dropIndex(['requires_reattempt']);
            $table->dropIndex(['delivery_failure_reason']);
            $table->dropColumn([
                'delivery_attempt_count',
                'delivery_failure_reason',
                'requires_reattempt',
                'last_delivery_attempt_at',
                'return_initiated_at',
            ]);
        });
    }
};
