<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payment_refunds')) {
            $this->normalizeExistingTable();

            return;
        }

        Schema::create('payment_refunds', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('payment_attempt_id');
            $table->unsignedInteger('order_id');
            $table->unsignedInteger('refund_id')->nullable();
            $table->string('provider', 100);
            $table->string('method_code', 100);
            $table->string('merchant_tran_id', 191);
            $table->string('gateway_tran_id', 191)->nullable();
            $table->string('gateway_refund_ref', 191)->nullable();
            $table->string('gateway_bank_tran_id', 191)->nullable();
            $table->decimal('requested_amount', 12, 4);
            $table->string('currency', 10);
            $table->text('reason')->nullable();
            $table->string('status', 50);
            $table->string('gateway_status', 100)->nullable();
            $table->unsignedBigInteger('requested_by_admin_id')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('last_error')->nullable();
            $table->json('meta')->nullable();
            $table->json('last_payload')->nullable();
            $table->timestamps();

            $table->foreign('payment_attempt_id')->references('id')->on('payment_attempts')->cascadeOnDelete();
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->foreign('refund_id')->references('id')->on('refunds')->nullOnDelete();
            $table->index(['order_id', 'status']);
            $table->index(['payment_attempt_id', 'status']);
            $table->unique(['provider', 'gateway_refund_ref'], 'payment_refunds_provider_gateway_refund_ref_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_refunds');
    }

    private function normalizeExistingTable(): void
    {
        DB::statement(<<<'SQL'
            ALTER TABLE `payment_refunds`
            MODIFY `payment_attempt_id` INT UNSIGNED NOT NULL,
            MODIFY `order_id` INT UNSIGNED NOT NULL,
            MODIFY `refund_id` INT UNSIGNED NULL
        SQL);

        if (! $this->indexExists('payment_refunds', 'payment_refunds_order_id_status_index')) {
            Schema::table('payment_refunds', function (Blueprint $table) {
                $table->index(['order_id', 'status']);
            });
        }

        if (! $this->indexExists('payment_refunds', 'payment_refunds_payment_attempt_id_status_index')) {
            Schema::table('payment_refunds', function (Blueprint $table) {
                $table->index(['payment_attempt_id', 'status']);
            });
        }

        if (! $this->indexExists('payment_refunds', 'payment_refunds_provider_gateway_refund_ref_unique')) {
            Schema::table('payment_refunds', function (Blueprint $table) {
                $table->unique(['provider', 'gateway_refund_ref'], 'payment_refunds_provider_gateway_refund_ref_unique');
            });
        }

        if (! $this->foreignKeyExists('payment_refunds', 'payment_refunds_payment_attempt_id_foreign')) {
            Schema::table('payment_refunds', function (Blueprint $table) {
                $table->foreign('payment_attempt_id')->references('id')->on('payment_attempts')->cascadeOnDelete();
            });
        }

        if (! $this->foreignKeyExists('payment_refunds', 'payment_refunds_order_id_foreign')) {
            Schema::table('payment_refunds', function (Blueprint $table) {
                $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            });
        }

        if (! $this->foreignKeyExists('payment_refunds', 'payment_refunds_refund_id_foreign')) {
            Schema::table('payment_refunds', function (Blueprint $table) {
                $table->foreign('refund_id')->references('id')->on('refunds')->nullOnDelete();
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        return DB::table('information_schema.table_constraints')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('constraint_name', $constraint)
            ->where('constraint_type', 'FOREIGN KEY')
            ->exists();
    }
};
