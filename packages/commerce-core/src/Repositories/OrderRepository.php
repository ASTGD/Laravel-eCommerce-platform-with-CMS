<?php

namespace Platform\CommerceCore\Repositories;

use Illuminate\Support\Facades\Event;
use Webkul\Sales\Models\Invoice;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Repositories\OrderRepository as BaseOrderRepository;

class OrderRepository extends BaseOrderRepository
{
    /**
     * Cancel order.
     */
    public function cancel($orderOrId)
    {
        $order = $this->resolveOrderInstance($orderOrId);

        if ($this->canForceCancelCodInvoiceOrder($order)) {
            return $this->forceCancelCodInvoiceOrder($order);
        }

        return parent::cancel($order);
    }

    /**
     * Update order status.
     */
    public function updateOrderStatus($order, $orderState = null)
    {
        Event::dispatch('sales.order.update-status.before', $order);

        if (
            ! empty($orderState)
            && ! $this->shouldKeepCodOrderOperational($order, $orderState)
        ) {
            $status = $orderState;
        } else {
            $status = $this->resolveCalculatedStatus($order);
        }

        $order->status = $status;
        $order->save();

        Event::dispatch('sales.order.update-status.after', $order);
    }

    protected function resolveCalculatedStatus($order): string
    {
        if ($this->isInCanceledState($order)) {
            return Order::STATUS_CANCELED;
        }

        if ($this->isInClosedState($order)) {
            return Order::STATUS_CLOSED;
        }

        if ($this->isInShippedState($order)) {
            return Order::STATUS_SHIPPED;
        }

        if (
            ! $this->hasStockableItems($order)
            && $this->isInCompletedState($order)
        ) {
            return Order::STATUS_COMPLETED;
        }

        if (
            $order->status === Order::STATUS_PENDING_PAYMENT
            && $order->payment?->method !== 'cashondelivery'
        ) {
            return Order::STATUS_PENDING_PAYMENT;
        }

        if ($order->status === Order::STATUS_PENDING) {
            return Order::STATUS_PENDING;
        }

        return Order::STATUS_PROCESSING;
    }

    protected function shouldKeepCodOrderOperational($order, ?string $orderState): bool
    {
        return $orderState === Order::STATUS_PENDING_PAYMENT
            && $order->payment?->method === 'cashondelivery';
    }

    protected function isInShippedState($order): bool
    {
        $hasStockableItems = false;

        foreach ($order->all_items()->get() as $item) {
            if (! $item->isStockable()) {
                continue;
            }

            $hasStockableItems = true;

            if ($item->qty_ordered > ($item->qty_shipped + $item->qty_canceled)) {
                return false;
            }
        }

        return $hasStockableItems;
    }

    protected function hasStockableItems($order): bool
    {
        foreach ($order->all_items()->get() as $item) {
            if (
                $item->isStockable()
                && $item->qty_ordered > 0
            ) {
                return true;
            }
        }

        return false;
    }

    protected function canForceCancelCodInvoiceOrder(Order $order): bool
    {
        $order = $order->fresh(['payment', 'invoices', 'items', 'shipments']) ?: $order->loadMissing(['payment', 'invoices', 'items', 'shipments']);

        if ($order->payment?->method !== 'cashondelivery') {
            return false;
        }

        if ($order->shipments->isNotEmpty()) {
            return false;
        }

        if ($order->invoices->isEmpty()) {
            return false;
        }

        return $order->invoices->every(function ($invoice): bool {
            return in_array($invoice->state, [
                Invoice::STATUS_PENDING,
                Invoice::STATUS_PENDING_PAYMENT,
            ], true);
        });
    }

    protected function forceCancelCodInvoiceOrder(Order $order): bool
    {
        $order = $order->fresh(['items.children', 'items.shipment_items', 'items.invoice_items', 'payment', 'invoices', 'shipments'])
            ?: $order->loadMissing(['items.children', 'items.shipment_items', 'items.invoice_items', 'payment', 'invoices', 'shipments']);

        Event::dispatch('sales.order.cancel.before', $order);

        foreach ($order->items as $item) {
            $orderItems = [];

            if ($item->getTypeInstance()->isComposite()) {
                foreach ($item->children as $child) {
                    $orderItems[] = $child;
                }
            } else {
                $orderItems[] = $item;
            }

            foreach ($orderItems as $orderItem) {
                $this->orderItemRepository->returnQtyToProductInventory($orderItem);

                $orderItem->qty_canceled = $orderItem->qty_ordered;
                $orderItem->save();

                if (
                    $orderItem->parent
                    && $orderItem->parent->qty_ordered
                ) {
                    $orderItem->parent->qty_canceled = $orderItem->parent->qty_ordered;
                    $orderItem->parent->save();
                }

                $this->downloadableLinkPurchasedRepository->updateStatus($orderItem, 'expired');
            }
        }

        $this->updateOrderStatus($order, Order::STATUS_CANCELED);

        Event::dispatch('sales.order.cancel.after', $order);

        return true;
    }
}
