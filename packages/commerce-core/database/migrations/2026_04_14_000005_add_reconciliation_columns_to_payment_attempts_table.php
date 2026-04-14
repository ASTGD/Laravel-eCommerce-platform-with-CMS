<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_attempts', function (Blueprint $table) {
            $table->timestamp('last_reconciled_at')->nullable()->after('last_ipn_at');
            $table->string('last_reconciled_status')->nullable()->after('last_reconciled_at');
            $table->string('last_reconciled_via')->nullable()->after('last_reconciled_status');
            $table->text('last_reconcile_error')->nullable()->after('last_reconciled_via');
        });
    }

    public function down(): void
    {
        Schema::table('payment_attempts', function (Blueprint $table) {
            $table->dropColumn([
                'last_reconciled_at',
                'last_reconciled_status',
                'last_reconciled_via',
                'last_reconcile_error',
            ]);
        });
    }
};
