<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settlement_batch_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('settlement_batch_id');
            $table->unsignedBigInteger('cod_settlement_id')->unique();
            $table->decimal('expected_amount', 12, 2)->default(0);
            $table->decimal('remitted_amount', 12, 2)->default(0);
            $table->decimal('adjustment_amount', 12, 2)->default(0);
            $table->decimal('short_amount', 12, 2)->default(0);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('settlement_batch_id')->references('id')->on('settlement_batches')->onDelete('cascade');
            $table->foreign('cod_settlement_id')->references('id')->on('cod_settlements')->onDelete('cascade');

            $table->index(['settlement_batch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settlement_batch_items');
    }
};
