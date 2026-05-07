<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Webkul\Sales\Models\Order;

return new class extends Migration
{
    public function up(): void
    {
        $this->setCodOrderStatusConfig(Order::STATUS_PENDING);

        DB::table('orders')
            ->join('order_payment', 'order_payment.order_id', '=', 'orders.id')
            ->leftJoin('shipments', 'shipments.order_id', '=', 'orders.id')
            ->where('order_payment.method', 'cashondelivery')
            ->where('orders.status', Order::STATUS_PENDING_PAYMENT)
            ->whereNull('shipments.id')
            ->update([
                'orders.status' => Order::STATUS_PENDING,
                'orders.updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        $this->setCodOrderStatusConfig(Order::STATUS_PENDING_PAYMENT);
    }

    protected function setCodOrderStatusConfig(string $value): void
    {
        $updated = DB::table('core_config')
            ->where('code', 'sales.payment_methods.cashondelivery.order_status')
            ->update([
                'value' => $value,
                'updated_at' => now(),
            ]);

        if ($updated > 0) {
            return;
        }

        DB::table('core_config')->insert([
            'code' => 'sales.payment_methods.cashondelivery.order_status',
            'value' => $value,
            'channel_code' => 'default',
            'locale_code' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
