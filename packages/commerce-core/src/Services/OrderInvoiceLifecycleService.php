<?php

namespace Platform\CommerceCore\Services;

use Webkul\Sales\Models\Invoice;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderRepository;

class OrderInvoiceLifecycleService
{
    public function __construct(
        protected InvoiceRepository $invoiceRepository,
        protected OrderRepository $orderRepository,
    ) {}

    public function ensureCodInvoiceForOrder(Order $order): ?Invoice
    {
        $order = $order->fresh(['payment', 'invoices', 'items']) ?: $order->loadMissing(['payment', 'invoices', 'items']);

        if ($order->payment?->method !== 'cashondelivery') {
            return $order->invoices->sortByDesc('id')->first();
        }

        if ($order->invoices->isNotEmpty()) {
            return $order->invoices->sortByDesc('id')->first();
        }

        $items = [];

        foreach ($order->items as $item) {
            if ((float) $item->qty_to_invoice > 0) {
                $items[$item->id] = $item->qty_to_invoice;
            }
        }

        if ($items === []) {
            return null;
        }

        return $this->invoiceRepository->create([
            'order_id' => $order->id,
            'invoice' => [
                'items' => $items,
            ],
        ], Invoice::STATUS_PENDING_PAYMENT);
    }

    public function markCodOrderInvoicesPaid(Order $order): void
    {
        $order = $order->fresh(['payment', 'invoices', 'items']) ?: $order->loadMissing(['payment', 'invoices', 'items']);

        if ($order->payment?->method !== 'cashondelivery') {
            return;
        }

        if ($order->invoices->isEmpty()) {
            $this->ensureCodInvoiceForOrder($order);

            $order->load('invoices');
        }

        foreach ($order->invoices as $invoice) {
            if (! in_array($invoice->state, [Invoice::STATUS_PENDING, Invoice::STATUS_PENDING_PAYMENT], true)) {
                continue;
            }

            $this->invoiceRepository->updateState($invoice, Invoice::STATUS_PAID);
        }

        $this->orderRepository->updateOrderStatus($order->fresh());
    }

    public function refundCodOrderInvoices(Order $order): void
    {
        $order = $order->fresh(['payment', 'invoices']) ?: $order->loadMissing(['payment', 'invoices']);

        if ($order->payment?->method !== 'cashondelivery') {
            return;
        }

        foreach ($order->invoices as $invoice) {
            if ($invoice->state === Invoice::STATUS_REFUNDED) {
                continue;
            }

            $this->invoiceRepository->updateState($invoice, Invoice::STATUS_REFUNDED);
        }
    }
}
