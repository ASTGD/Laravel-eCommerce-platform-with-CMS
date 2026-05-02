<?php

namespace Platform\CommerceCore\Services;

use Platform\CommerceCore\Models\CodSettlement;
use Platform\CommerceCore\Models\ShipmentRecord;
use Platform\CommerceCore\Support\PaymentMethodRegistry;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderTransaction;

class OrderFinanceSummaryService
{
    public function summarize(Order $order): array
    {
        $order->loadMissing(['payment', 'invoices', 'shipments', 'refunds']);

        $paymentMethod = $order->payment?->method;
        $isCod = $paymentMethod === 'cashondelivery';
        $codSettlements = CodSettlement::query()
            ->where('order_id', $order->id)
            ->get();
        $shipmentRecords = ShipmentRecord::query()
            ->where('order_id', $order->id)
            ->latest('id')
            ->get();

        return [
            'payment_method' => core()->getConfigData('sales.payment_methods.'.$paymentMethod.'.title')
                ?: PaymentMethodRegistry::labelForCode($paymentMethod)
                ?: 'N/A',
            'payment_state' => $isCod
                ? $this->codPaymentState($codSettlements)
                : $this->invoicePaymentState($order),
            'invoice_state' => $this->invoiceState($order),
            'shipment_state' => $this->shipmentState($order, $shipmentRecords),
            'cod_state' => $isCod ? $this->codPaymentState($codSettlements) : 'Not COD',
        ];
    }

    protected function codPaymentState($codSettlements): string
    {
        if ($codSettlements->isEmpty()) {
            return 'Awaiting COD';
        }

        $netAmount = (float) $codSettlements->sum('net_amount');
        $collectedAmount = (float) $codSettlements->sum('collected_amount');
        $remittedAmount = (float) $codSettlements->sum('remitted_amount');

        if ($netAmount > 0 && $remittedAmount >= $netAmount) {
            return 'Settled';
        }

        if ($remittedAmount > 0) {
            return 'Partially Remitted';
        }

        if ($collectedAmount > 0) {
            return 'Collected by Courier';
        }

        return 'Awaiting COD';
    }

    protected function invoicePaymentState(Order $order): string
    {
        $refundedAmount = (float) $order->refunds->sum('base_grand_total');

        if ($refundedAmount > 0) {
            return $refundedAmount >= (float) $order->base_grand_total ? 'Refunded' : 'Partially Refunded';
        }

        $invoiceTotal = (float) $order->invoices->sum('base_grand_total');
        $transactionTotal = (float) OrderTransaction::query()
            ->where('order_id', $order->id)
            ->where('status', 'paid')
            ->sum('amount');
        $payableTotal = $invoiceTotal > 0 ? $invoiceTotal : (float) $order->base_grand_total;

        if ($transactionTotal <= 0) {
            return 'Pending';
        }

        if ($transactionTotal < $payableTotal) {
            return 'Partially Paid';
        }

        return 'Paid';
    }

    protected function invoiceState(Order $order): string
    {
        if ($order->invoices->isEmpty()) {
            return 'No invoice';
        }

        $paidCount = $order->invoices->where('state', 'paid')->count();

        if ($paidCount === $order->invoices->count()) {
            return $order->invoices->count().' invoice(s) / Paid';
        }

        if ($paidCount > 0) {
            return $order->invoices->count().' invoice(s) / Partially Paid';
        }

        return $order->invoices->count().' invoice(s) / Pending';
    }

    protected function shipmentState(Order $order, $shipmentRecords): string
    {
        if ($shipmentRecords->isNotEmpty()) {
            $latest = $shipmentRecords->first();

            return $shipmentRecords->count().' shipment record(s) / '.$latest->status_label;
        }

        if ($order->shipments->isNotEmpty()) {
            return $order->shipments->count().' native shipment(s)';
        }

        return 'No shipment';
    }
}
