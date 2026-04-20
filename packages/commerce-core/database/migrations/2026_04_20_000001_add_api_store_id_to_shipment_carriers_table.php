<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipment_carriers', function (Blueprint $table): void {
            $table->unsignedBigInteger('api_store_id')->nullable()->after('api_base_url');
        });
    }

    public function down(): void
    {
        Schema::table('shipment_carriers', function (Blueprint $table): void {
            $table->dropColumn('api_store_id');
        });
    }
};
