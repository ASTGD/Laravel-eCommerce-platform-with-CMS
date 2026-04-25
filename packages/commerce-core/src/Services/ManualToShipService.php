<?php

namespace Platform\CommerceCore\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Platform\CommerceCore\Models\ShipmentCarrier;
use Platform\CommerceCore\Models\ShipmentRecord;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderItem;

class ManualToShipService
{
    public function paginateNeedsBookingOrders(
        int $perPage = 10,
        ?string $search = null,
        string $pageName = 'page',
    ): LengthAwarePaginator {
        $orders = $this->needsBookingOrdersCollection();

        if ($search = $this->normalizeSearchTerm($search)) {
            $orders = $orders->filter(function (Order $order) use ($search) {
                return Str::contains($this->searchableOrderText($order), $search);
            })->values();
        }

        $page = LengthAwarePaginator::resolveCurrentPage($pageName);
        $items = $orders
            ->slice(($page - 1) * $perPage, $perPage)
            ->values()
            ->map(fn (Order $order) => $this->presentOrder($order));

        return $this->newPaginator($items, $orders->count(), $perPage, $page, $pageName);
    }

    public function paginateReadyShipments(
        int $perPage = 10,
        ?string $search = null,
        ?int $carrierId = null,
        ?string $preparedDate = null,
        ?string $handoverMode = null,
        string $pageName = 'page',
    ): LengthAwarePaginator {
        $query = ShipmentRecord::query()
            ->with([
                'order',
                'carrier',
                'packer',
                'handoverBatch' => fn ($query) => $query->withCount('shipments'),
            ])
            ->where('status', ShipmentRecord::STATUS_READY_FOR_PICKUP);

        if ($carrierId) {
            $query->where('shipment_carrier_id', $carrierId);
        }

        if ($preparedDate) {
            $query->whereDate('packed_at', $preparedDate);
        }

        if ($handoverMode && array_key_exists($handoverMode, ShipmentRecord::handoverModeLabels())) {
            $query->where('handover_mode', $handoverMode);
        }

        if ($search = $this->normalizeSearchTerm($search)) {
            $query->where(function (Builder $builder) use ($search) {
                $builder
                    ->where('tracking_number', 'like', '%'.$search.'%')
                    ->orWhere('recipient_name', 'like', '%'.$search.'%')
                    ->orWhere('recipient_phone', 'like', '%'.$search.'%')
                    ->orWhere('recipient_address', 'like', '%'.$search.'%')
                    ->orWhereHas('order', function (Builder $orderQuery) use ($search) {
                        $orderQuery
                            ->where('increment_id', 'like', '%'.$search.'%')
                            ->orWhere('customer_first_name', 'like', '%'.$search.'%')
                            ->orWhere('customer_last_name', 'like', '%'.$search.'%')
                            ->orWhere('customer_email', 'like', '%'.$search.'%');
                    });
            });
        }

        return $query
            ->orderBy('shipment_carrier_id')
            ->orderByDesc('packed_at')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], $pageName)
            ->withQueryString();
    }

    public function queueCounts(): array
    {
        return [
            'needs_booking' => $this->needsBookingOrdersCollection()->count(),
            'ready_for_handover' => ShipmentRecord::query()
                ->where('status', ShipmentRecord::STATUS_READY_FOR_PICKUP)
                ->count(),
        ];
    }

    public function activeCarriers(): Collection
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

    public function readyCarrierOptions(): Collection
    {
        return ShipmentCarrier::query()
            ->select('shipment_carriers.id', 'shipment_carriers.name')
            ->join('shipment_records', 'shipment_records.shipment_carrier_id', '=', 'shipment_carriers.id')
            ->where('shipment_records.status', ShipmentRecord::STATUS_READY_FOR_PICKUP)
            ->distinct()
            ->orderBy('shipment_carriers.name')
            ->get();
    }

    public function bookingValidationRules(bool $requireTracking = true): array
    {
        $trackingRule = [$requireTracking ? 'required' : 'nullable', 'string', 'max:255'];

        return [
            'shipment.carrier_id' => 'required|exists:shipment_carriers,id',
            'shipment.track_number' => $trackingRule,
            'shipment.public_tracking_url' => 'nullable|url|max:1000',
            'shipment.stock_checked' => 'required|boolean',
            'shipment.package_count' => 'required|integer|min:1|max:999',
            'shipment.package_weight_kg' => 'nullable|numeric|min:0|max:999.99',
            'shipment.package_dimensions' => 'nullable|string|max:255',
            'shipment.is_fragile' => 'nullable|boolean',
            'shipment.special_handling' => 'nullable|string|max:1000',
            'shipment.internal_note' => 'nullable|string|max:1000',
            'shipment.courier_note' => 'nullable|string|max:1000',
            'shipment.handover_mode' => 'required|in:'.implode(',', array_keys(ShipmentRecord::handoverModeLabels())),
        ];
    }

    public function bookingValidationMessages(): array
    {
        return [
            'shipment.carrier_id.required' => 'Select the courier for this parcel.',
            'shipment.track_number.required' => 'Enter the tracking number before saving this booking.',
            'shipment.public_tracking_url.url' => 'Enter a valid tracking URL or leave it blank.',
            'shipment.stock_checked.required' => 'Confirm that stock has been checked before saving this parcel.',
            'shipment.package_count.required' => 'Enter how many parcel pieces are being handed over.',
            'shipment.package_count.min' => 'Parcel count must be at least 1.',
            'shipment.package_weight_kg.min' => 'Package weight cannot be negative.',
            'shipment.handover_mode.required' => 'Choose how this parcel will be handed over to the courier.',
            'shipment.internal_note.max' => 'Internal note must be 1000 characters or fewer.',
            'shipment.courier_note.max' => 'Courier note must be 1000 characters or fewer.',
            'shipment.special_handling.max' => 'Special handling note must be 1000 characters or fewer.',
        ];
    }

    public function bookingValidationAttributes(): array
    {
        return [
            'shipment.carrier_id' => 'courier',
            'shipment.track_number' => 'tracking number',
            'shipment.public_tracking_url' => 'tracking URL',
            'shipment.stock_checked' => 'stock check',
            'shipment.package_count' => 'package count',
            'shipment.package_weight_kg' => 'package weight',
            'shipment.package_dimensions' => 'package dimensions',
            'shipment.is_fragile' => 'fragile handling',
            'shipment.special_handling' => 'special handling',
            'shipment.internal_note' => 'internal note',
            'shipment.courier_note' => 'courier note',
            'shipment.handover_mode' => 'handover mode',
        ];
    }

    public function draftForOrder(Order $order): ?ShipmentRecord
    {
        return ShipmentRecord::query()
            ->with(['carrier', 'order.payment', 'order.addresses', 'order.items', 'inventorySource', 'packer'])
            ->where('order_id', $order->id)
            ->where('status', ShipmentRecord::STATUS_DRAFT)
            ->whereNull('native_shipment_id')
            ->latest('id')
            ->first();
    }

    public function saveBookingDraft(Order $order, array $shipmentData, ?int $actorAdminId = null): ShipmentRecord
    {
        $order->loadMissing(['payment', 'addresses', 'items', 'channel.inventory_sources']);

        $booking = $this->resolveBookingBlueprint($order);
        $carrier = ShipmentCarrier::query()->active()->find((int) ($shipmentData['carrier_id'] ?? 0));
        $shippingAddress = $order->shipping_address;
        $inventorySourceId = (int) ($shipmentData['source'] ?? $booking['inventory_source_id'] ?? 0);
        $inventorySource = $order->channel?->inventory_sources?->firstWhere('id', $inventorySourceId);
        $codAmount = $order->payment?->method === 'cashondelivery'
            ? (float) ($order->base_grand_total ?? 0)
            : 0.0;
        $packageWeight = blank($shipmentData['package_weight_kg'] ?? null)
            ? null
            : round((float) $shipmentData['package_weight_kg'], 2);
        $internalNote = trim((string) ($shipmentData['internal_note'] ?? ''));
        $specialHandling = trim((string) ($shipmentData['special_handling'] ?? ''));
        $courierNote = trim((string) ($shipmentData['courier_note'] ?? ''));

        $draft = $this->draftForOrder($order) ?: new ShipmentRecord([
            'order_id' => $order->id,
            'status' => ShipmentRecord::STATUS_DRAFT,
            'created_by_admin_id' => $actorAdminId,
        ]);

        $draft->fill([
            'order_id' => $order->id,
            'shipment_carrier_id' => $carrier?->id,
            'inventory_source_id' => $inventorySourceId > 0 ? $inventorySourceId : null,
            'updated_by_admin_id' => $actorAdminId,
            'packed_by_admin_id' => $draft->packed_by_admin_id ?: $actorAdminId,
            'status' => ShipmentRecord::STATUS_DRAFT,
            'carrier_name_snapshot' => $carrier?->name,
            'tracking_number' => trim((string) ($shipmentData['track_number'] ?? '')),
            'stock_checked' => true,
            'packed_at' => $draft->packed_at ?: now(),
            'package_count' => max(1, (int) ($shipmentData['package_count'] ?? 1)),
            'package_weight_kg' => $packageWeight,
            'package_dimensions' => trim((string) ($shipmentData['package_dimensions'] ?? '')) ?: null,
            'is_fragile' => (bool) ($shipmentData['is_fragile'] ?? false),
            'handover_mode' => array_key_exists((string) ($shipmentData['handover_mode'] ?? ''), ShipmentRecord::handoverModeLabels())
                ? (string) $shipmentData['handover_mode']
                : ShipmentRecord::HANDOVER_MODE_COURIER_PICKUP,
            'special_handling' => $specialHandling !== '' ? $specialHandling : null,
            'internal_note' => $internalNote !== '' ? $internalNote : null,
            'courier_note' => $courierNote !== '' ? $courierNote : null,
            'public_tracking_url' => trim((string) ($shipmentData['public_tracking_url'] ?? '')) ?: null,
            'inventory_source_name' => $inventorySource?->name ?: $booking['inventory_source_name'],
            'origin_label' => $inventorySource?->name ?: $booking['inventory_source_name'],
            'destination_country' => $shippingAddress?->country,
            'destination_region' => $shippingAddress?->state,
            'destination_city' => $shippingAddress?->city,
            'recipient_name' => $shippingAddress?->name ?: $order->customer_full_name,
            'recipient_phone' => $shippingAddress?->phone,
            'recipient_address' => $shippingAddress?->address,
            'cod_amount_expected' => $codAmount,
            'carrier_fee_amount' => (float) ($order->base_shipping_amount ?? 0),
            'cod_fee_amount' => (float) ($carrier?->default_cod_fee_amount ?? $draft->cod_fee_amount ?? 0),
            'return_fee_amount' => (float) ($carrier?->default_return_fee_amount ?? $draft->return_fee_amount ?? 0),
            'notes' => $internalNote !== '' ? $internalNote : null,
        ]);

        $draft->net_remittable_amount = max(
            0,
            (float) $draft->cod_amount_expected
                - (float) $draft->carrier_fee_amount
                - (float) $draft->cod_fee_amount
                - (float) $draft->return_fee_amount
        );

        if (! $draft->exists) {
            $draft->created_by_admin_id = $actorAdminId;
        }

        $draft->save();

        return $draft->fresh(['carrier', 'order.payment', 'order.addresses', 'order.items', 'inventorySource', 'packer']);
    }

    public function markDraftDocumentsGenerated(ShipmentRecord $draft, string $document, ?int $actorAdminId = null): ShipmentRecord
    {
        $hash = $this->bookingDocumentHash($draft);
        $now = now();

        if (in_array($document, ['label', 'both'], true)) {
            $draft->label_generated_hash = $hash;
            $draft->label_generated_at = $now;
        }

        if (in_array($document, ['invoice', 'both'], true)) {
            $draft->invoice_generated_hash = $hash;
            $draft->invoice_generated_at = $now;
        }

        $draft->updated_by_admin_id = $actorAdminId;
        $draft->save();

        return $draft->fresh(['carrier', 'order.payment', 'order.addresses', 'order.items', 'inventorySource', 'packer']);
    }

    public function documentStatuses(ShipmentRecord $draft): array
    {
        $hash = $this->bookingDocumentHash($draft);
        $labelStatus = $this->documentStatus($draft->label_generated_hash, $hash);
        $invoiceStatus = $this->documentStatus($draft->invoice_generated_hash, $hash);

        return [
            'hash' => $hash,
            'label' => [
                'status' => $labelStatus,
                'label' => $this->documentStatusLabel($labelStatus),
                'generated_at' => $draft->label_generated_at?->toIso8601String(),
            ],
            'invoice' => [
                'status' => $invoiceStatus,
                'label' => $this->documentStatusLabel($invoiceStatus),
                'generated_at' => $draft->invoice_generated_at?->toIso8601String(),
            ],
            'can_complete' => $labelStatus === 'ready' && $invoiceStatus === 'ready',
        ];
    }

    public function documentIsReady(ShipmentRecord $draft, string $document): bool
    {
        $statuses = $this->documentStatuses($draft);

        return match ($document) {
            'label' => $statuses['label']['status'] === 'ready',
            'invoice' => $statuses['invoice']['status'] === 'ready',
            'both' => $statuses['label']['status'] === 'ready' && $statuses['invoice']['status'] === 'ready',
            default => false,
        };
    }

    public function bookingDraftResponse(ShipmentRecord $draft): array
    {
        $statuses = $this->documentStatuses($draft);

        return [
            'id' => $draft->id,
            'status' => $draft->status,
            'shipment' => $this->draftShipmentData($draft),
            'packed_by_label' => $draft->packer?->name,
            'packed_at_label' => $draft->packed_at?->format('d M Y, h:i A'),
            'document_statuses' => $statuses,
            'can_complete' => $statuses['can_complete'],
        ];
    }

    public function draftShipmentData(ShipmentRecord $draft): array
    {
        $order = $draft->order ?: Order::query()->findOrFail($draft->order_id);
        $booking = $this->resolveBookingBlueprint($order->loadMissing(['items.product.inventories', 'channel.inventory_sources']));

        return [
            'source' => $draft->inventory_source_id ?: $booking['inventory_source_id'],
            'items' => $booking['items_payload'],
            'workflow_stage' => 'ready_for_handover',
            'carrier_id' => $draft->shipment_carrier_id,
            'carrier_title' => $draft->carrier?->name ?: $draft->carrier_name_snapshot,
            'track_number' => $draft->tracking_number,
            'public_tracking_url' => $draft->public_tracking_url,
            'stock_checked' => true,
            'package_count' => max(1, (int) $draft->package_count),
            'package_weight_kg' => $draft->package_weight_kg,
            'package_dimensions' => $draft->package_dimensions,
            'is_fragile' => (bool) $draft->is_fragile,
            'special_handling' => $draft->special_handling,
            'internal_note' => $draft->internal_note,
            'note' => $draft->internal_note,
            'courier_note' => $draft->courier_note,
            'handover_mode' => $draft->handover_mode ?: ShipmentRecord::HANDOVER_MODE_COURIER_PICKUP,
        ];
    }

    public function bookingDocumentHash(ShipmentRecord $draft): string
    {
        $draft->loadMissing(['carrier', 'order.payment', 'order.addresses', 'order.items']);
        $order = $draft->order;
        $shippingAddress = $order?->shipping_address;

        $payload = [
            'order' => [
                'id' => $order?->id,
                'increment_id' => $order?->increment_id,
                'customer_name' => $order?->customer_full_name,
                'payment_method' => $order?->payment?->method,
                'grand_total' => number_format((float) ($order?->base_grand_total ?? 0), 2, '.', ''),
            ],
            'shipping' => [
                'name' => $shippingAddress?->name,
                'phone' => $shippingAddress?->phone,
                'address' => $this->formatAddress($shippingAddress?->address, [
                    $shippingAddress?->city,
                    $shippingAddress?->state,
                    $shippingAddress?->country,
                    $shippingAddress?->postcode,
                ]),
            ],
            'items' => $order?->items
                ? $order->items
                    ->filter(fn (OrderItem $item) => (float) $item->qty_to_ship > 0)
                    ->map(fn (OrderItem $item) => [
                        'id' => $item->id,
                        'sku' => $item->sku,
                        'name' => $item->name,
                        'qty_to_ship' => number_format((float) $item->qty_to_ship, 4, '.', ''),
                        'base_price' => number_format((float) ($item->base_price ?? 0), 2, '.', ''),
                    ])
                    ->values()
                    ->all()
                : [],
            'booking' => [
                'carrier_id' => $draft->shipment_carrier_id,
                'carrier_name' => $draft->carrier?->name ?: $draft->carrier_name_snapshot,
                'tracking_number' => $draft->tracking_number,
                'tracking_url' => $draft->public_tracking_url,
                'package_count' => (int) $draft->package_count,
                'package_weight_kg' => $draft->package_weight_kg === null ? null : number_format((float) $draft->package_weight_kg, 2, '.', ''),
                'package_dimensions' => $draft->package_dimensions,
                'is_fragile' => (bool) $draft->is_fragile,
                'handover_mode' => $draft->handover_mode,
                'special_handling' => $draft->special_handling,
                'courier_note' => $draft->courier_note,
            ],
        ];

        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
    }

    public function printableBookingData(Order $order, array $shipmentData, string $document): array
    {
        $order->loadMissing(['payment', 'addresses', 'items']);

        $carrier = ShipmentCarrier::query()->find((int) ($shipmentData['carrier_id'] ?? 0));
        $shippingAddress = $order->shipping_address;
        $items = $order->items
            ->filter(fn (OrderItem $item) => (float) $item->qty_to_ship > 0)
            ->values();
        $totalQty = (int) round((float) $items->sum('qty_to_ship'));
        $codAmount = $order->payment?->method === 'cashondelivery'
            ? (float) ($order->base_grand_total ?? 0)
            : 0.0;

        return [
            'document' => $document,
            'merchant' => $this->merchantDetails(),
            'order' => $order,
            'carrier' => $carrier,
            'tracking_number' => trim((string) ($shipmentData['track_number'] ?? '')),
            'tracking_url' => trim((string) ($shipmentData['public_tracking_url'] ?? '')),
            'handover_mode_label' => ShipmentRecord::handoverModeLabels()[$shipmentData['handover_mode'] ?? '']
                ?? 'Courier Pickup',
            'payment_label' => $codAmount > 0 ? 'Cash on Delivery' : 'Prepaid',
            'cod_amount' => $codAmount,
            'cod_amount_formatted' => core()->formatBasePrice($codAmount),
            'order_total_formatted' => core()->formatBasePrice((float) ($order->base_grand_total ?? 0)),
            'customer_name' => $order->customer_full_name,
            'customer_phone' => $shippingAddress?->phone,
            'delivery_address' => $this->formatAddress($shippingAddress?->address, [
                $shippingAddress?->city,
                $shippingAddress?->state,
                $shippingAddress?->country,
                $shippingAddress?->postcode,
            ]),
            'items' => $items->map(function (OrderItem $item) {
                return [
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'qty' => (float) $item->qty_to_ship,
                    'price_formatted' => core()->formatBasePrice((float) ($item->base_price ?? 0)),
                    'subtotal_formatted' => core()->formatBasePrice((float) (($item->base_price ?? 0) * ($item->qty_to_ship ?? 0))),
                ];
            }),
            'item_summary' => $items
                ->map(fn (OrderItem $item) => sprintf('%s x %s', $item->name, $this->formatQuantity((float) $item->qty_to_ship)))
                ->implode(', '),
            'total_qty' => $totalQty,
            'package_count' => max(1, (int) ($shipmentData['package_count'] ?? 1)),
            'package_weight_kg' => blank($shipmentData['package_weight_kg'] ?? null) ? null : number_format((float) $shipmentData['package_weight_kg'], 2),
            'package_dimensions' => trim((string) ($shipmentData['package_dimensions'] ?? '')) ?: null,
            'is_fragile' => (bool) ($shipmentData['is_fragile'] ?? false),
            'special_handling' => trim((string) ($shipmentData['special_handling'] ?? '')) ?: null,
            'internal_note' => trim((string) ($shipmentData['internal_note'] ?? '')) ?: null,
            'courier_note' => trim((string) ($shipmentData['courier_note'] ?? '')) ?: null,
        ];
    }

    protected function needsBookingOrdersCollection(): Collection
    {
        $orderIdsAlreadyInShipmentFlow = ShipmentRecord::query()
            ->whereNotIn('status', [
                ShipmentRecord::STATUS_DRAFT,
                ShipmentRecord::STATUS_CANCELED,
            ])
            ->pluck('order_id')
            ->filter()
            ->unique()
            ->all();

        return Order::query()
            ->with([
                'payment',
                'addresses',
                'items.product.inventories',
                'channel.inventory_sources',
            ])
            ->where('status', Order::STATUS_PROCESSING)
            ->latest('id')
            ->get()
            ->reject(fn (Order $order) => in_array((int) $order->id, $orderIdsAlreadyInShipmentFlow, true))
            ->filter(fn (Order $order) => $order->canShip())
            ->values();
    }

    protected function normalizeSearchTerm(?string $search): ?string
    {
        $search = trim((string) $search);

        return $search !== '' ? Str::lower($search) : null;
    }

    protected function searchableOrderText(Order $order): string
    {
        $shippingAddress = $order->shipping_address;

        return Str::lower(implode(' ', array_filter([
            $order->increment_id,
            $order->customer_full_name,
            $order->customer_email,
            $shippingAddress?->phone,
            $shippingAddress?->address,
            $shippingAddress?->city,
            $shippingAddress?->state,
            $shippingAddress?->country,
        ])));
    }

    protected function presentOrder(Order $order): array
    {
        $shippingAddress = $order->shipping_address;
        $booking = $this->resolveBookingBlueprint($order);
        $draft = $this->draftForOrder($order);
        $shippableItems = $order->items
            ->filter(fn (OrderItem $item) => (float) $item->qty_to_ship > 0)
            ->values();
        $codAmount = $order->payment?->method === 'cashondelivery'
            ? (float) ($order->base_grand_total ?? 0)
            : 0.0;

        return [
            'order' => $order,
            'customer_label' => $order->customer_full_name,
            'phone_label' => $shippingAddress?->phone ?: 'No phone added',
            'address_label' => $this->formatAddress($shippingAddress?->address, [
                $shippingAddress?->city,
                $shippingAddress?->state,
                $shippingAddress?->country,
            ]),
            'payment_label' => $codAmount > 0 ? 'COD' : 'Prepaid',
            'cod_amount_formatted' => core()->formatBasePrice($codAmount),
            'order_amount_formatted' => core()->formatBasePrice((float) $order->base_grand_total),
            'stock_check_label' => $booking['stock_check_label'],
            'stock_check_reason' => $booking['stock_check_reason'],
            'can_book' => $booking['can_book'],
            'inventory_source_id' => $booking['inventory_source_id'],
            'inventory_source_name' => $booking['inventory_source_name'],
            'items_payload' => $booking['items_payload'],
            'draft' => $draft,
            'draft_document_statuses' => $draft ? $this->documentStatuses($draft) : null,
            'items_summary' => $shippableItems
                ->map(fn (OrderItem $item) => sprintf('%s x %s', $item->name, $this->formatQuantity((float) $item->qty_to_ship)))
                ->values(),
            'items_count' => $shippableItems->count(),
            'total_qty' => $this->formatQuantity((float) $shippableItems->sum('qty_to_ship')),
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

    public function merchantDetails(): array
    {
        $channel = core()->getCurrentChannel();
        $storeName = trim((string) core()->getConfigData('sales.shipping.origin.store_name'));
        $originAddress = trim((string) core()->getConfigData('sales.shipping.origin.address'));
        $originCity = trim((string) core()->getConfigData('sales.shipping.origin.city'));
        $originState = trim((string) core()->getConfigData('sales.shipping.origin.state'));
        $originCountry = trim((string) core()->getConfigData('sales.shipping.origin.country'));
        $originZipcode = trim((string) core()->getConfigData('sales.shipping.origin.zipcode'));

        return [
            'name' => $storeName !== ''
                ? $storeName
                : ($channel->name ?? config('app.name')),
            'contact' => trim((string) core()->getConfigData('sales.shipping.origin.contact')) ?: null,
            'address' => $this->formatAddress($originAddress, [
                $originCity,
                $originState,
                $originCountry,
                $originZipcode,
            ]),
        ];
    }

    protected function formatAddress(mixed $address, array $segments = []): string
    {
        $lines = array_filter(array_merge(Arr::wrap($address), $segments));

        return $lines !== [] ? implode(', ', $lines) : 'No address added';
    }

    protected function formatQuantity(float $quantity): string
    {
        if ((int) $quantity == $quantity) {
            return (string) (int) $quantity;
        }

        return number_format($quantity, 2);
    }

    protected function documentStatus(?string $savedHash, string $currentHash): string
    {
        if (! $savedHash) {
            return 'not_generated';
        }

        return hash_equals($savedHash, $currentHash) ? 'ready' : 'needs_reprint';
    }

    protected function documentStatusLabel(string $status): string
    {
        return match ($status) {
            'ready' => 'Ready',
            'needs_reprint' => 'Needs reprint',
            default => 'Not generated',
        };
    }

    protected function newPaginator(
        Collection $items,
        int $total,
        int $perPage,
        int $page,
        string $pageName = 'page',
    ): LengthAwarePaginator {
        return new LengthAwarePaginator(
            items: $items,
            total: $total,
            perPage: $perPage,
            currentPage: $page,
            options: [
                'path' => request()->url(),
                'query' => request()->query(),
                'pageName' => $pageName,
            ],
        );
    }
}
