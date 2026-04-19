<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_record_id')->constrained('shipment_records')->cascadeOnDelete();
            $table->unsignedInteger('actor_admin_id')->nullable();
            $table->string('event_type', 50);
            $table->string('status_after_event', 50)->nullable();
            $table->timestamp('event_at');
            $table->text('note')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('actor_admin_id')->references('id')->on('admins')->onDelete('set null');
            $table->index(['shipment_record_id', 'event_at']);
            $table->index(['status_after_event']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_events');
    }
};
