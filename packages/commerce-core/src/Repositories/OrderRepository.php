<?php

namespace Platform\CommerceCore\Repositories;

use Illuminate\Support\Facades\Event;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Repositories\OrderRepository as BaseOrderRepository;

class OrderRepository extends BaseOrderRepository
{
    /**
     * Update order status.
     */
    public function updateOrderStatus($order, $orderState = null)
    {
        Event::dispatch('sales.order.update-status.before', $order);

        if (! empty($orderState)) {
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

        if ($order->status === Order::STATUS_PENDING_PAYMENT) {
            return Order::STATUS_PENDING_PAYMENT;
        }

        if ($order->status === Order::STATUS_PENDING) {
            return Order::STATUS_PENDING;
        }

        return Order::STATUS_PROCESSING;
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
}
