<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipment_records', function (Blueprint $table) {
            $table->timestamp('last_tracking_synced_at')->nullable()->after('return_initiated_at');
            $table->string('last_tracking_sync_status', 50)->nullable()->after('last_tracking_synced_at');
            $table->text('last_tracking_sync_message')->nullable()->after('last_tracking_sync_status');

            $table->index(['last_tracking_sync_status']);
        });
    }

    public function down(): void
    {
        Schema::table('shipment_records', function (Blueprint $table) {
            $table->dropIndex(['last_tracking_sync_status']);
            $table->dropColumn([
                'last_tracking_synced_at',
                'last_tracking_sync_status',
                'last_tracking_sync_message',
            ]);
        });
    }
};
