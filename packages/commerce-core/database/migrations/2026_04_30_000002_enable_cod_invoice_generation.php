<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->setCodInvoiceConfig('sales.payment_methods.cashondelivery.generate_invoice', '1');
        $this->setCodInvoiceConfig('sales.payment_methods.cashondelivery.invoice_status', 'pending_payment');
        $this->setCodInvoiceConfig('sales.payment_methods.cashondelivery.order_status', 'pending_payment');
    }

    public function down(): void
    {
        $this->setCodInvoiceConfig('sales.payment_methods.cashondelivery.generate_invoice', '0');
        $this->setCodInvoiceConfig('sales.payment_methods.cashondelivery.invoice_status', 'pending');
        $this->setCodInvoiceConfig('sales.payment_methods.cashondelivery.order_status', 'pending');
    }

    protected function setCodInvoiceConfig(string $code, string $value): void
    {
        $updated = DB::table('core_config')
            ->where('code', $code)
            ->update([
                'value' => $value,
                'updated_at' => now(),
            ]);

        if ($updated > 0) {
            return;
        }

        DB::table('core_config')->insert([
            'code' => $code,
            'value' => $value,
            'channel_code' => 'default',
            'locale_code' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
