<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipment_carriers', function (Blueprint $table) {
            $table->string('integration_driver', 100)->nullable()->after('tracking_url_template');
            $table->boolean('tracking_sync_enabled')->default(false)->after('integration_driver');
            $table->string('api_base_url', 500)->nullable()->after('tracking_sync_enabled');
            $table->string('api_username')->nullable()->after('api_base_url');
            $table->text('api_password')->nullable()->after('api_username');
            $table->text('api_key')->nullable()->after('api_password');
            $table->text('api_secret')->nullable()->after('api_key');
            $table->text('webhook_secret')->nullable()->after('api_secret');

            $table->index(['integration_driver']);
            $table->index(['tracking_sync_enabled']);
        });
    }

    public function down(): void
    {
        Schema::table('shipment_carriers', function (Blueprint $table) {
            $table->dropIndex(['integration_driver']);
            $table->dropIndex(['tracking_sync_enabled']);
            $table->dropColumn([
                'integration_driver',
                'tracking_sync_enabled',
                'api_base_url',
                'api_username',
                'api_password',
                'api_key',
                'api_secret',
                'webhook_secret',
            ]);
        });
    }
};
