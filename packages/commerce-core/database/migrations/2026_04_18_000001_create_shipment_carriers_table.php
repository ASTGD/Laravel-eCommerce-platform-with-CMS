<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_carriers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('tracking_url_template', 500)->nullable();
            $table->boolean('supports_cod')->default(true);
            $table->string('default_cod_fee_type')->nullable();
            $table->decimal('default_cod_fee_amount', 12, 2)->default(0);
            $table->decimal('default_return_fee_amount', 12, 2)->default(0);
            $table->string('default_payout_method')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_carriers');
    }
};
