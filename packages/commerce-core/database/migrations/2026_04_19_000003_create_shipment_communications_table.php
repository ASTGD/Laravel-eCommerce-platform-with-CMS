<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_communications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_record_id')->constrained('shipment_records')->cascadeOnDelete();
            $table->foreignId('shipment_event_id')->nullable()->constrained('shipment_events')->nullOnDelete();
            $table->string('audience', 32);
            $table->string('channel', 32)->default('email');
            $table->string('notification_key', 64);
            $table->string('recipient_name')->nullable();
            $table->string('recipient_email')->nullable();
            $table->string('subject')->nullable();
            $table->string('status', 32);
            $table->text('reason')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['shipment_record_id', 'status']);
            $table->index(['shipment_event_id', 'notification_key']);
            $table->index(['audience', 'channel', 'notification_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_communications');
    }
};
