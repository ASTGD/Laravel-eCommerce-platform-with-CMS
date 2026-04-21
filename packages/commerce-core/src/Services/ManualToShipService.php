<?php

namespace Platform\CommerceCore\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Platform\CommerceCore\Models\ShipmentCarrier;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderItem;

class ManualToShipService
{
    public function paginateOrders(int $perPage = 15): LengthAwarePaginator
    {
        $orders = Order::query()
            ->with([
                'payment',
                'addresses',
                'items.product.inventories',
                'channel.inventory_sources',
            ])
            ->where('status', Order::STATUS_PROCESSING)
            ->latest('id')
            ->get()
            ->filter(fn (Order $order) => $order->canShip())
            ->values();

        $page = LengthAwarePaginator::resolveCurrentPage();
        $items = $orders
            ->slice(($page - 1) * $perPage, $perPage)
            ->values()
            ->map(fn (Order $order) => $this->presentOrder($order));

        return new LengthAwarePaginator(
            items: $items,
            total: $orders->count(),
            perPage: $perPage,
            currentPage: $page,
            options: [
                'path' => request()->url(),
                'query' => request()->query(),
            ],
        );
    }

    public function activeCarriers()
    {
        return ShipmentCarrier::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'tracking_url_template',
            ]);
    }

    protected function presentOrder(Order $order): array
    {
        $shippingAddress = $order->shipping_address;
        $booking = $this->resolveBookingBlueprint($order);

        return [
            'order' => $order,
            'customer_label' => $order->customer_full_name,
            'phone_label' => $shippingAddress?->phone ?: 'No phone added',
            'address_label' => $this->formatAddress($shippingAddress?->address, [
                $shippingAddress?->city,
                $shippingAddress?->state,
                $shippingAddress?->country,
            ]),
            'payment_label' => $order->payment?->method === 'cashondelivery' ? 'COD' : 'Prepaid',
            'order_amount_formatted' => core()->formatBasePrice((float) $order->base_grand_total),
            'stock_check_label' => $booking['stock_check_label'],
            'stock_check_reason' => $booking['stock_check_reason'],
            'can_book' => $booking['can_book'],
            'inventory_source_id' => $booking['inventory_source_id'],
            'inventory_source_name' => $booking['inventory_source_name'],
            'items_payload' => $booking['items_payload'],
        ];
    }

    protected function resolveBookingBlueprint(Order $order): array
    {
        $inventorySource = $order->channel?->inventory_sources?->sortBy('id')->first();

        if (! $inventorySource) {
            return [
                'stock_check_label' => 'No stock source',
                'stock_check_reason' => 'Add an inventory source to this sales channel before booking a shipment.',
                'can_book' => false,
                'inventory_source_id' => null,
                'inventory_source_name' => null,
                'items_payload' => [],
            ];
        }

        $itemsPayload = [];

        foreach ($order->items as $item) {
            if (! $item->canShip()) {
                continue;
            }

            if (! $this->hasAvailableInventory($item, (int) $inventorySource->id)) {
                return [
                    'stock_check_label' => 'Needs stock check',
                    'stock_check_reason' => 'The default stock source does not currently have enough sellable quantity for every shippable item.',
                    'can_book' => false,
                    'inventory_source_id' => (int) $inventorySource->id,
                    'inventory_source_name' => $inventorySource->name,
                    'items_payload' => [],
                ];
            }

            $itemsPayload[$item->id][$inventorySource->id] = (float) $item->qty_to_ship;
        }

        if ($itemsPayload === []) {
            return [
                'stock_check_label' => 'Nothing to ship',
                'stock_check_reason' => 'This order does not currently have any stockable quantity left to ship.',
                'can_book' => false,
                'inventory_source_id' => (int) $inventorySource->id,
                'inventory_source_name' => $inventorySource->name,
                'items_payload' => [],
            ];
        }

        return [
            'stock_check_label' => 'Ready',
            'stock_check_reason' => 'The default stock source has enough quantity for the remaining shipment.',
            'can_book' => true,
            'inventory_source_id' => (int) $inventorySource->id,
            'inventory_source_name' => $inventorySource->name,
            'items_payload' => $itemsPayload,
        ];
    }

    protected function hasAvailableInventory(OrderItem $item, int $inventorySourceId): bool
    {
        $qtyToShip = (float) $item->qty_to_ship;

        if ($qtyToShip <= 0) {
            return false;
        }

        if ($item->getTypeInstance()->isComposite()) {
            foreach ($item->children as $child) {
                if (! $child->qty_ordered) {
                    continue;
                }

                $requiredQty = ($child->qty_ordered / max(1, $item->qty_ordered)) * $qtyToShip;
                $availableQty = (float) $child->product?->inventories()
                    ->where('inventory_source_id', $inventorySourceId)
                    ->sum('qty');

                if ($child->qty_to_ship < $requiredQty || $availableQty < $requiredQty) {
                    return false;
                }
            }

            return true;
        }

        $availableQty = (float) $item->product?->inventories()
            ->where('inventory_source_id', $inventorySourceId)
            ->sum('qty');

        return $availableQty >= $qtyToShip;
    }

    protected function formatAddress(mixed $address, array $segments = []): string
    {
        $lines = array_filter(array_merge(Arr::wrap($address), $segments));

        return $lines !== [] ? implode(', ', $lines) : 'No shipping address added';
    }
}
