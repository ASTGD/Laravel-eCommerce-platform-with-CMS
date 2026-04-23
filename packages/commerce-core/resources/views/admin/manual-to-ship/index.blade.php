@php
    $needsBookingOrders ??= collect();
    $readyShipments ??= collect();
    $oldSelectedShipmentIds = collect(old('shipment_record_ids', []))
        ->map(fn ($id) => (string) $id)
        ->filter()
        ->values();
    $readyShipmentGroups = $readyShipments->getCollection()
        ->groupBy(function ($shipmentRecord) {
            return (string) ($shipmentRecord->shipment_carrier_id ?: ('manual-'.md5((string) $shipmentRecord->carrier_name_snapshot)));
        })
        ->map(function ($shipments, $groupKey) {
            $firstShipment = $shipments->first();
            $carrierName = $firstShipment->carrier?->name ?: $firstShipment->carrier_name_snapshot ?: 'Manual Courier';

            return [
                'key' => (string) $groupKey,
                'dom_id' => 'ready-group-'.($firstShipment->shipment_carrier_id ?: md5($carrierName)),
                'carrier_id' => $firstShipment->shipment_carrier_id,
                'carrier_name' => $carrierName,
                'shipments' => $shipments,
                'shipment_count' => $shipments->count(),
                'parcel_count' => (int) $shipments->sum(fn ($shipmentRecord) => max(1, (int) $shipmentRecord->package_count)),
                'total_cod_amount' => round((float) $shipments->sum('cod_amount_expected'), 2),
            ];
        })
        ->sortBy(fn (array $group) => strtolower($group['carrier_name']))
        ->values();
    $restoreHandoverModal = filled(old('handover_action')) || $errors->hasAny([
        'shipment_record_ids',
        'shipment_record_ids.*',
        'handover_type',
        'handover_at',
        'receiver_name',
        'notes',
        'selected_shipments',
    ]);
    $restoredHandoverAction = old('handover_action', 'draft');
    $needsSectionQuery = array_filter([
        'ready_search' => request('ready_search'),
        'ready_per_page' => request('ready_per_page'),
        'ready_page' => request('ready_page'),
        'ready_carrier_id' => request('ready_carrier_id'),
        'ready_prepared_date' => request('ready_prepared_date'),
        'ready_handover_mode' => request('ready_handover_mode'),
    ], fn ($value) => filled($value));
    $readySectionQuery = array_filter([
        'needs_search' => request('needs_search'),
        'needs_per_page' => request('needs_per_page'),
        'needs_page' => request('needs_page'),
    ], fn ($value) => filled($value));
@endphp

<x-admin::layouts>
    <x-slot:title>
        To Ship
    </x-slot>

    <div class="grid gap-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="grid gap-1">
                <p class="text-xl font-bold text-gray-800 dark:text-white">
                    To Ship
                </p>

                <p class="text-sm text-gray-600 dark:text-gray-300">
                    Prepare parcels, print shipping documents, and confirm courier handover before they move into In Delivery.
                </p>
            </div>
        </div>

        <section id="needs-booking" class="grid gap-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="grid gap-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">
                            Needs Booking
                        </h2>

                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700 dark:bg-gray-800 dark:text-gray-200">
                            {{ $queueCounts['needs_booking'] ?? 0 }}
                        </span>
                    </div>

                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Orders that still need parcel preparation and courier booking.
                    </p>
                </div>
            </div>

            <x-commerce-core::admin.basic-list-toolbar
                :paginator="$needsBookingOrders"
                :search-action="route('admin.sales.to-ship.index').'#needs-booking'"
                search-placeholder="Search orders"
                search-name="needs_search"
                page-name="needs_page"
                per-page-name="needs_per_page"
                :search-value="request('needs_search')"
                :per-page="(int) request('needs_per_page', $needsBookingOrders->perPage())"
                :preserve-query="$needsSectionQuery"
            >
                <x-slot:filters>
                    <div class="grid gap-3 p-4 text-sm text-gray-600 dark:text-gray-300">
                        <p>
                            Search confirmed orders by order number, customer, phone, or delivery address.
                        </p>

                        <p>
                            Once a parcel is packed and booked here, it moves to <span class="font-semibold">Parcel Ready for Handover</span> until the courier pickup or drop-off is confirmed.
                        </p>
                    </div>
                </x-slot:filters>
            </x-commerce-core::admin.basic-list-toolbar>

            @if ($shipmentCarriers->isEmpty())
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-100">
                    Add at least one active courier service before preparing bookings from this page.

                    <a
                        href="{{ route('admin.sales.carriers.create') }}"
                        class="ml-1 font-semibold text-blue-600 hover:underline"
                    >
                        Add Courier Service
                    </a>
                </div>
            @endif

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                @if ($needsBookingOrders->isEmpty())
                    <div class="p-10 text-center text-sm text-gray-600 dark:text-gray-300">
                        No confirmed orders are waiting for booking right now.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <x-admin::table class="min-w-[1400px]">
                            <thead class="bg-slate-50 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                                <tr>
                                    <x-admin::table.th>Order</x-admin::table.th>
                                    <x-admin::table.th>Customer</x-admin::table.th>
                                    <x-admin::table.th>Phone</x-admin::table.th>
                                    <x-admin::table.th>Address</x-admin::table.th>
                                    <x-admin::table.th>COD / Prepaid</x-admin::table.th>
                                    <x-admin::table.th>Order Amount</x-admin::table.th>
                                    <x-admin::table.th>Action</x-admin::table.th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($needsBookingOrders as $row)
                                    @php
                                        $order = $row['order'];
                                        $isRestoringModal = (string) old('booking_order_id') === (string) $order->id;
                                    @endphp

                                    <tr class="border-t border-slate-200 align-top dark:border-gray-800">
                                        <x-admin::table.td class="whitespace-normal">
                                            <div class="grid gap-1">
                                                <a
                                                    href="{{ route('admin.sales.orders.view', $order->id) }}"
                                                    class="font-semibold text-blue-600 hover:underline"
                                                >
                                                    #{{ $order->increment_id }}
                                                </a>

                                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $order->status_label }}
                                                </span>
                                            </div>
                                        </x-admin::table.td>

                                        <x-admin::table.td class="whitespace-normal">
                                            {{ $row['customer_label'] }}
                                        </x-admin::table.td>

                                        <x-admin::table.td class="whitespace-normal">
                                            {{ $row['phone_label'] }}
                                        </x-admin::table.td>

                                        <x-admin::table.td class="whitespace-normal">
                                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                                {{ $row['address_label'] }}
                                            </span>
                                        </x-admin::table.td>

                                        <x-admin::table.td class="whitespace-normal">
                                            <div class="grid gap-1">
                                                <span>{{ $row['payment_label'] }}</span>

                                                @if ($row['payment_label'] === 'COD')
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                                        {{ $row['cod_amount_formatted'] }}
                                                    </span>
                                                @endif
                                            </div>
                                        </x-admin::table.td>

                                        <x-admin::table.td>
                                            {{ $row['order_amount_formatted'] }}
                                        </x-admin::table.td>

                                        <x-admin::table.td class="whitespace-normal">
                                            @if (bouncer()->hasPermission('sales.shipments.create') && $row['can_book'] && $shipmentCarriers->isNotEmpty())
                                                <x-admin::modal
                                                    :is-active="$isRestoringModal"
                                                    box-style="width: min(1120px, calc(100vw - 2rem)); max-width: min(1120px, calc(100vw - 2rem));"
                                                >
                                                    <x-slot:toggle>
                                                        <button
                                                            type="button"
                                                            class="inline-flex rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700"
                                                        >
                                                            Book Shipment
                                                        </button>
                                                    </x-slot>

                                                    <x-slot:header>
                                                        <div class="grid gap-1">
                                                            <p class="text-lg font-bold text-gray-800 dark:text-white">
                                                                Pack and Book Shipment
                                                            </p>

                                                            <p class="text-sm font-normal text-gray-600 dark:text-gray-300">
                                                                Order #{{ $order->increment_id }} will move to Parcel Ready for Handover after you save the booking.
                                                            </p>
                                                        </div>
                                                    </x-slot>

                                                    <x-slot:content>
                                                        <x-admin::form
                                                            method="POST"
                                                            :action="route('admin.sales.shipments.store', $order->id)"
                                                        >
                                                            <input type="hidden" name="redirect_to" value="to_ship">
                                                            <input type="hidden" name="booking_order_id" value="{{ $order->id }}">
                                                            <input type="hidden" name="shipment[source]" value="{{ $row['inventory_source_id'] }}">
                                                            <input type="hidden" name="shipment[workflow_stage]" value="ready_for_handover">

                                                            @foreach ($row['items_payload'] as $itemId => $sourcePayload)
                                                                @foreach ($sourcePayload as $sourceId => $qty)
                                                                    <input
                                                                        type="hidden"
                                                                        name="shipment[items][{{ $itemId }}][{{ $sourceId }}]"
                                                                        value="{{ $qty }}"
                                                                    >
                                                                @endforeach
                                                            @endforeach

                                                            <div class="grid gap-5">
                                                                <section class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-gray-800 dark:bg-gray-800/60">
                                                                    <div class="mb-3 grid gap-1">
                                                                        <p class="text-base font-semibold text-gray-800 dark:text-white">
                                                                            1. Order Snapshot
                                                                        </p>

                                                                        <p class="text-sm text-gray-600 dark:text-gray-300">
                                                                            Review the order before you pack and book the parcel.
                                                                        </p>
                                                                    </div>

                                                                    <div class="grid gap-4 lg:grid-cols-2">
                                                                        <div class="grid gap-3">
                                                                            <div>
                                                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Order No</p>
                                                                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">#{{ $order->increment_id }}</p>
                                                                            </div>

                                                                            <div>
                                                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Customer</p>
                                                                                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $row['customer_label'] }}</p>
                                                                            </div>

                                                                            <div>
                                                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Phone</p>
                                                                                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $row['phone_label'] }}</p>
                                                                            </div>

                                                                            <div>
                                                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Address</p>
                                                                                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $row['address_label'] }}</p>
                                                                            </div>
                                                                        </div>

                                                                        <div class="grid gap-3">
                                                                            <div class="grid grid-cols-2 gap-3">
                                                                                <div>
                                                                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Payment Type</p>
                                                                                    <p class="text-sm text-gray-700 dark:text-gray-300">{{ $row['payment_label'] }}</p>
                                                                                </div>

                                                                                <div>
                                                                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">COD Amount</p>
                                                                                    <p class="text-sm text-gray-700 dark:text-gray-300">{{ $row['payment_label'] === 'COD' ? $row['cod_amount_formatted'] : 'Prepaid' }}</p>
                                                                                </div>
                                                                            </div>

                                                                            <div>
                                                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Item Summary</p>
                                                                                <div class="mt-1 grid gap-1 text-sm text-gray-700 dark:text-gray-300">
                                                                                    @foreach ($row['items_summary'] as $summaryLine)
                                                                                        <p>{{ $summaryLine }}</p>
                                                                                    @endforeach
                                                                                </div>
                                                                            </div>

                                                                            <div>
                                                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Qty</p>
                                                                                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $row['total_qty'] }}</p>
                                                                            </div>

                                                                            <div>
                                                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Stock Source</p>
                                                                                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $row['inventory_source_name'] }}</p>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </section>

                                                                <section class="rounded-xl border border-slate-200 p-4 dark:border-gray-800">
                                                                    <div class="mb-3 grid gap-1">
                                                                        <p class="text-base font-semibold text-gray-800 dark:text-white">
                                                                            2. Pick &amp; Pack
                                                                        </p>

                                                                        <p class="text-sm text-gray-600 dark:text-gray-300">
                                                                            Record how this parcel was prepared before handover.
                                                                        </p>
                                                                    </div>

                                                                    <div class="grid gap-4 lg:grid-cols-2">
                                                                        <x-admin::form.control-group class="!mb-0">
                                                                            <input type="hidden" name="shipment[stock_checked]" value="0">

                                                                            <label class="inline-flex items-center gap-3 text-sm font-semibold text-gray-800 dark:text-gray-100">
                                                                                <input
                                                                                    type="checkbox"
                                                                                    name="shipment[stock_checked]"
                                                                                    value="1"
                                                                                    class="h-4 w-4 rounded border-slate-300 text-blue-600"
                                                                                    @checked($isRestoringModal ? (bool) old('shipment.stock_checked', true) : true)
                                                                                >

                                                                                Stock checked
                                                                            </label>

                                                                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                                                                Confirm that items were checked before this parcel is packed.
                                                                            </p>

                                                                            <x-admin::form.control-group.error control-name="shipment.stock_checked" />
                                                                        </x-admin::form.control-group>

                                                                        <div class="grid gap-1 rounded-lg bg-slate-50 px-4 py-3 text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                                                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Packed By</p>
                                                                            <p class="font-semibold">{{ $currentAdminName }}</p>
                                                                            <p class="text-xs text-gray-500 dark:text-gray-400">Packed time is saved automatically when you save the booking.</p>
                                                                        </div>

                                                                        <x-admin::form.control-group class="!mb-0">
                                                                            <x-admin::form.control-group.label class="required">
                                                                                Package Count
                                                                            </x-admin::form.control-group.label>

                                                                            <x-admin::form.control-group.control
                                                                                type="number"
                                                                                name="shipment[package_count]"
                                                                                :value="$isRestoringModal ? old('shipment.package_count', 1) : 1"
                                                                                rules="required|min_value:1"
                                                                                :label="'Package Count'"
                                                                                :placeholder="'1'"
                                                                            />

                                                                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                                                                Enter how many parcel pieces will be handed over.
                                                                            </p>

                                                                            <x-admin::form.control-group.error control-name="shipment.package_count" />
                                                                        </x-admin::form.control-group>

                                                                        <x-admin::form.control-group class="!mb-0">
                                                                            <x-admin::form.control-group.label>
                                                                                Optional Weight (kg)
                                                                            </x-admin::form.control-group.label>

                                                                            <x-admin::form.control-group.control
                                                                                type="number"
                                                                                name="shipment[package_weight_kg]"
                                                                                :value="$isRestoringModal ? old('shipment.package_weight_kg') : ''"
                                                                                :label="'Optional Weight (kg)'"
                                                                                :placeholder="'0.50'"
                                                                                step="0.01"
                                                                                min="0"
                                                                            />

                                                                            <x-admin::form.control-group.error control-name="shipment.package_weight_kg" />
                                                                        </x-admin::form.control-group>

                                                                        <x-admin::form.control-group class="!mb-0">
                                                                            <x-admin::form.control-group.label>
                                                                                Optional Dimensions
                                                                            </x-admin::form.control-group.label>

                                                                            <x-admin::form.control-group.control
                                                                                type="text"
                                                                                name="shipment[package_dimensions]"
                                                                                :value="$isRestoringModal ? old('shipment.package_dimensions') : ''"
                                                                                :label="'Optional Dimensions'"
                                                                                :placeholder="'12 x 8 x 5 in'"
                                                                            />

                                                                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                                                                Use any format your staff understands.
                                                                            </p>

                                                                            <x-admin::form.control-group.error control-name="shipment.package_dimensions" />
                                                                        </x-admin::form.control-group>

                                                                        <x-admin::form.control-group class="!mb-0">
                                                                            <x-admin::form.control-group.label class="required">
                                                                                Planned Handover Mode
                                                                            </x-admin::form.control-group.label>

                                                                            <x-admin::form.control-group.control
                                                                                type="select"
                                                                                name="shipment[handover_mode]"
                                                                                :label="'Planned Handover Mode'"
                                                                            >
                                                                                @foreach ($handoverModes as $handoverModeValue => $handoverModeLabel)
                                                                                    <option
                                                                                        value="{{ $handoverModeValue }}"
                                                                                        @selected($isRestoringModal ? old('shipment.handover_mode', \Platform\CommerceCore\Models\ShipmentRecord::HANDOVER_MODE_COURIER_PICKUP) === $handoverModeValue : $handoverModeValue === \Platform\CommerceCore\Models\ShipmentRecord::HANDOVER_MODE_COURIER_PICKUP)
                                                                                    >
                                                                                        {{ $handoverModeLabel }}
                                                                                    </option>
                                                                                @endforeach
                                                                            </x-admin::form.control-group.control>

                                                                            <x-admin::form.control-group.error control-name="shipment.handover_mode" />
                                                                        </x-admin::form.control-group>

                                                                        <x-admin::form.control-group class="!mb-0 lg:col-span-2">
                                                                            <input type="hidden" name="shipment[is_fragile]" value="0">

                                                                            <label class="inline-flex items-center gap-3 text-sm font-semibold text-gray-800 dark:text-gray-100">
                                                                                <input
                                                                                    type="checkbox"
                                                                                    name="shipment[is_fragile]"
                                                                                    value="1"
                                                                                    class="h-4 w-4 rounded border-slate-300 text-blue-600"
                                                                                    @checked($isRestoringModal ? (bool) old('shipment.is_fragile') : false)
                                                                                >

                                                                                Fragile parcel / special care needed
                                                                            </label>

                                                                            <x-admin::form.control-group.error control-name="shipment.is_fragile" />
                                                                        </x-admin::form.control-group>

                                                                        <x-admin::form.control-group class="!mb-0 lg:col-span-2">
                                                                            <x-admin::form.control-group.label>
                                                                                Fragile / Special Handling
                                                                            </x-admin::form.control-group.label>

                                                                            <x-admin::form.control-group.control
                                                                                type="textarea"
                                                                                name="shipment[special_handling]"
                                                                                :value="$isRestoringModal ? old('shipment.special_handling') : ''"
                                                                                :label="'Fragile / Special Handling'"
                                                                                :placeholder="'Glass item, keep upright, call before pickup'"
                                                                            />

                                                                            <x-admin::form.control-group.error control-name="shipment.special_handling" />
                                                                        </x-admin::form.control-group>

                                                                        <x-admin::form.control-group class="!mb-0 lg:col-span-2">
                                                                            <x-admin::form.control-group.label>
                                                                                Internal Note
                                                                            </x-admin::form.control-group.label>

                                                                            <x-admin::form.control-group.control
                                                                                type="textarea"
                                                                                name="shipment[internal_note]"
                                                                                :value="$isRestoringModal ? old('shipment.internal_note') : ''"
                                                                                :label="'Internal Note'"
                                                                                :placeholder="'Packing note for your warehouse or handover team'"
                                                                            />

                                                                            <x-admin::form.control-group.error control-name="shipment.internal_note" />
                                                                        </x-admin::form.control-group>
                                                                    </div>
                                                                </section>

                                                                <section class="rounded-xl border border-slate-200 p-4 dark:border-gray-800">
                                                                    <div class="mb-3 grid gap-1">
                                                                        <p class="text-base font-semibold text-gray-800 dark:text-white">
                                                                            3. Courier Booking
                                                                        </p>

                                                                        <p class="text-sm text-gray-600 dark:text-gray-300">
                                                                            Save the courier details your team will use to follow the parcel.
                                                                        </p>
                                                                    </div>

                                                                    <div class="grid gap-4 lg:grid-cols-2">
                                                                        <x-admin::form.control-group class="!mb-0">
                                                                            <x-admin::form.control-group.label class="required">
                                                                                Courier
                                                                            </x-admin::form.control-group.label>

                                                                            <x-admin::form.control-group.control
                                                                                type="select"
                                                                                name="shipment[carrier_id]"
                                                                                rules="required"
                                                                                :label="'Courier'"
                                                                            >
                                                                                <option value="">Select courier</option>

                                                                                @foreach ($shipmentCarriers as $shipmentCarrier)
                                                                                    <option
                                                                                        value="{{ $shipmentCarrier->id }}"
                                                                                        @selected($isRestoringModal && (string) old('shipment.carrier_id') === (string) $shipmentCarrier->id)
                                                                                    >
                                                                                        {{ $shipmentCarrier->name }}
                                                                                    </option>
                                                                                @endforeach
                                                                            </x-admin::form.control-group.control>

                                                                            <x-admin::form.control-group.error control-name="shipment.carrier_id" />
                                                                        </x-admin::form.control-group>

                                                                        <x-admin::form.control-group class="!mb-0">
                                                                            <x-admin::form.control-group.label class="required">
                                                                                Tracking Number
                                                                            </x-admin::form.control-group.label>

                                                                            <x-admin::form.control-group.control
                                                                                type="text"
                                                                                name="shipment[track_number]"
                                                                                :value="$isRestoringModal ? old('shipment.track_number') : ''"
                                                                                rules="required"
                                                                                :label="'Tracking Number'"
                                                                                :placeholder="'Enter tracking number'"
                                                                            />

                                                                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                                                                Use the number, consignment ID, or booking reference your team will use for follow-up.
                                                                            </p>

                                                                            <x-admin::form.control-group.error control-name="shipment.track_number" />
                                                                        </x-admin::form.control-group>

                                                                        <x-admin::form.control-group class="!mb-0">
                                                                            <x-admin::form.control-group.label>
                                                                                Tracking URL
                                                                            </x-admin::form.control-group.label>

                                                                            <x-admin::form.control-group.control
                                                                                type="text"
                                                                                name="shipment[public_tracking_url]"
                                                                                :value="$isRestoringModal ? old('shipment.public_tracking_url') : ''"
                                                                                :label="'Tracking URL'"
                                                                                :placeholder="'https://courier.example/track/ABC123'"
                                                                            />

                                                                            <x-admin::form.control-group.error control-name="shipment.public_tracking_url" />
                                                                        </x-admin::form.control-group>

                                                                        <x-admin::form.control-group class="!mb-0 lg:col-span-2">
                                                                            <x-admin::form.control-group.label>
                                                                                Courier Note
                                                                            </x-admin::form.control-group.label>

                                                                            <x-admin::form.control-group.control
                                                                                type="textarea"
                                                                                name="shipment[courier_note]"
                                                                                :value="$isRestoringModal ? old('shipment.courier_note') : ''"
                                                                                :label="'Courier Note'"
                                                                                :placeholder="'Pickup note or courier reference for the handover team'"
                                                                            />

                                                                            <x-admin::form.control-group.error control-name="shipment.courier_note" />
                                                                        </x-admin::form.control-group>
                                                                    </div>
                                                                </section>

                                                                <section class="rounded-xl border border-slate-200 p-4 dark:border-gray-800">
                                                                    <div class="mb-4 grid gap-1">
                                                                        <p class="text-base font-semibold text-gray-800 dark:text-white">
                                                                            4. Print &amp; Save
                                                                        </p>

                                                                        <p class="text-sm text-gray-600 dark:text-gray-300">
                                                                            Print the parcel label or invoice if needed, then save the booking to move this parcel into Parcel Ready for Handover.
                                                                        </p>
                                                                    </div>

                                                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                                                        <div class="flex flex-wrap gap-2">
                                                                            <button
                                                                                type="button"
                                                                                data-shipment-print-preview="{{ route('admin.sales.to-ship.print-documents', [$order, 'document' => 'label']) }}"
                                                                                data-print-title="Parcel Label Preview"
                                                                                class="inline-flex rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-slate-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                                                                            >
                                                                                Print Parcel Label
                                                                            </button>

                                                                            <button
                                                                                type="button"
                                                                                data-shipment-print-preview="{{ route('admin.sales.to-ship.print-documents', [$order, 'document' => 'invoice']) }}"
                                                                                data-print-title="Invoice Preview"
                                                                                class="inline-flex rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-slate-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                                                                            >
                                                                                Print Invoice
                                                                            </button>

                                                                            <button
                                                                                type="button"
                                                                                data-shipment-print-preview="{{ route('admin.sales.to-ship.print-documents', [$order, 'document' => 'both']) }}"
                                                                                data-print-title="Parcel Label and Invoice Preview"
                                                                                class="inline-flex rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-slate-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                                                                            >
                                                                                Print Both
                                                                            </button>
                                                                        </div>

                                                                        <button
                                                                            type="submit"
                                                                            class="primary-button"
                                                                        >
                                                                            Save Booking
                                                                        </button>
                                                                    </div>
                                                                </section>
                                                            </div>
                                                        </x-admin::form>
                                                    </x-slot:content>
                                                </x-admin::modal>
                                            @else
                                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $shipmentCarriers->isEmpty() ? 'Add a courier service first.' : $row['stock_check_reason'] }}
                                                </span>
                                            @endif
                                        </x-admin::table.td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </x-admin::table>
                    </div>

                    <div class="border-t border-slate-200 px-6 py-4 dark:border-gray-800">
                        {{ $needsBookingOrders->links() }}
                    </div>
                @endif
            </div>
        </section>

        <section id="parcel-ready-for-handover" class="grid gap-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="grid gap-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-white">
                            Parcel Ready for Handover
                        </h2>

                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700 dark:bg-gray-800 dark:text-gray-200">
                            {{ $queueCounts['ready_for_handover'] ?? 0 }}
                        </span>
                    </div>

                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Prepared parcels waiting for actual courier pickup or staff drop-off confirmation.
                    </p>
                </div>
            </div>

            <x-commerce-core::admin.basic-list-toolbar
                :paginator="$readyShipments"
                :search-action="route('admin.sales.to-ship.index').'#parcel-ready-for-handover'"
                search-placeholder="Search parcels"
                search-name="ready_search"
                page-name="ready_page"
                per-page-name="ready_per_page"
                :search-value="request('ready_search')"
                :per-page="(int) request('ready_per_page', $readyShipments->perPage())"
                :preserve-query="array_merge($readySectionQuery, array_filter([
                    'ready_carrier_id' => request('ready_carrier_id'),
                    'ready_prepared_date' => request('ready_prepared_date'),
                    'ready_handover_mode' => request('ready_handover_mode'),
                ], fn ($value) => filled($value)))"
            >
                <x-slot:filters>
                    <div class="grid gap-4 p-4">
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            Find ready parcels by courier, prepared date, handover mode, order number, tracking number, or customer.
                        </p>

                        <form method="GET" action="{{ route('admin.sales.to-ship.index') }}#parcel-ready-for-handover" class="grid gap-4">
                            @foreach ($readySectionQuery as $queryKey => $queryValue)
                                <input type="hidden" name="{{ $queryKey }}" value="{{ $queryValue }}">
                            @endforeach

                            <input type="hidden" name="ready_search" value="{{ request('ready_search') }}">
                            <input type="hidden" name="ready_per_page" value="{{ request('ready_per_page', $readyShipments->perPage()) }}">
                            <input type="hidden" name="ready_page" value="1">

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="grid gap-1.5">
                                    <label for="ready_carrier_id" class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                        Courier
                                    </label>

                                    <select
                                        id="ready_carrier_id"
                                        name="ready_carrier_id"
                                        class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 shadow-sm focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                    >
                                        <option value="">All couriers</option>

                                        @foreach ($readyCarriers as $carrier)
                                            <option value="{{ $carrier->id }}" @selected((string) request('ready_carrier_id') === (string) $carrier->id)>
                                                {{ $carrier->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="grid gap-1.5">
                                    <label for="ready_prepared_date" class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                        Prepared Date
                                    </label>

                                    <input
                                        id="ready_prepared_date"
                                        type="date"
                                        name="ready_prepared_date"
                                        value="{{ request('ready_prepared_date') }}"
                                        class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 shadow-sm focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                    >
                                </div>

                                <div class="grid gap-1.5 md:col-span-2">
                                    <label for="ready_handover_mode" class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                        Handover Mode
                                    </label>

                                    <select
                                        id="ready_handover_mode"
                                        name="ready_handover_mode"
                                        class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 shadow-sm focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                    >
                                        <option value="">All handover modes</option>

                                        @foreach ($handoverModes as $handoverModeValue => $handoverModeLabel)
                                            <option value="{{ $handoverModeValue }}" @selected(request('ready_handover_mode') === $handoverModeValue)>
                                                {{ $handoverModeLabel }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <button
                                    type="submit"
                                    class="inline-flex rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700"
                                >
                                    Apply Filter
                                </button>

                                @if (request()->filled('ready_carrier_id') || request()->filled('ready_prepared_date') || request()->filled('ready_handover_mode'))
                                    <a
                                        href="{{ route('admin.sales.to-ship.index', array_filter([
                                            ...$readySectionQuery,
                                            'ready_search' => request('ready_search'),
                                            'ready_per_page' => request('ready_per_page'),
                                        ])) }}#parcel-ready-for-handover"
                                        class="inline-flex rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-slate-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                                    >
                                        Clear
                                    </a>
                                @endif
                            </div>
                        </form>
                    </div>
                </x-slot:filters>
            </x-commerce-core::admin.basic-list-toolbar>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                @if ($readyShipments->isEmpty())
                    <div class="p-10 text-center text-sm text-gray-600 dark:text-gray-300">
                        No parcels are waiting for actual courier handover right now.
                    </div>
                @else
                    <form method="POST" id="handover-batch-form" class="grid gap-4 p-4">
                        @csrf

                        <input type="hidden" id="handover_action" name="handover_action" value="{{ $restoredHandoverAction }}">

                        <div class="grid gap-3 rounded-xl border border-slate-200 bg-slate-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/30">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div class="grid gap-1">
                                    <div
                                        id="ready-shipment-selection-summary"
                                        class="text-sm font-semibold text-gray-800 dark:text-gray-100"
                                    >
                                        No parcels selected
                                    </div>

                                    <div
                                        id="ready-shipment-selection-hint"
                                        class="text-xs text-gray-500 dark:text-gray-400"
                                    >
                                        Select parcels from one courier to create a handover sheet or manifest.
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    <button
                                        type="button"
                                        class="inline-flex rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-slate-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                                        onclick="window.toggleReadyShipmentSelection(true)"
                                    >
                                        Select All
                                    </button>

                                    <button
                                        type="button"
                                        data-handover-bulk-action="draft"
                                        class="inline-flex rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                                        disabled
                                    >
                                        Create Handover Sheet
                                    </button>

                                    <button
                                        type="button"
                                        data-handover-bulk-action="print"
                                        class="inline-flex rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                                        disabled
                                    >
                                        Print Manifest
                                    </button>

                                    <button
                                        type="button"
                                        data-handover-bulk-action="confirm"
                                        class="primary-button disabled:cursor-not-allowed disabled:opacity-50"
                                        disabled
                                    >
                                        Confirm Handover
                                    </button>
                                </div>
                            </div>

                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                Use the courier parent checkbox to select every parcel for that courier, or choose parcel rows individually.
                            </div>
                        </div>

                        <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-gray-800">
                            <x-admin::table class="min-w-[1320px]">
                                <thead class="bg-white text-gray-800 dark:bg-gray-900 dark:text-gray-100">
                                    <tr>
                                        <x-admin::table.th class="w-[56px]">
                                            <input
                                                type="checkbox"
                                                data-ready-select-all
                                                class="h-4 w-4 rounded border-slate-300 text-blue-600"
                                                onchange="window.toggleReadyShipmentSelection(this.checked)"
                                            >
                                        </x-admin::table.th>
                                        <x-admin::table.th>Order / Courier</x-admin::table.th>
                                        <x-admin::table.th>Customer / Selection</x-admin::table.th>
                                        <x-admin::table.th>Tracking Number</x-admin::table.th>
                                        <x-admin::table.th>Parcel Count</x-admin::table.th>
                                        <x-admin::table.th>COD Amount</x-admin::table.th>
                                        <x-admin::table.th>Packed / Prepared Time</x-admin::table.th>
                                        <x-admin::table.th>Handover Mode</x-admin::table.th>
                                        <x-admin::table.th>Note / Status</x-admin::table.th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($readyShipmentGroups as $group)
                                        <tr
                                            data-ready-group-block="{{ $group['dom_id'] }}"
                                            data-ready-carrier-name="{{ $group['carrier_name'] }}"
                                            class="border-t border-slate-200 align-top dark:border-gray-800"
                                        >
                                            <x-admin::table.td colspan="9" class="!p-0">
                                                <div class="flex items-start justify-between gap-4 bg-slate-100 px-4 py-4 dark:bg-gray-950/60">
                                                    <div class="flex min-w-0 items-start gap-4">
                                                        <input
                                                            type="checkbox"
                                                            data-ready-parent-checkbox="{{ $group['dom_id'] }}"
                                                            class="mt-1 h-4 w-4 rounded border-slate-300 text-blue-600"
                                                            onchange="window.toggleReadyShipmentGroupSelection('{{ $group['dom_id'] }}', this.checked)"
                                                        >

                                                        <div class="flex min-w-0 flex-col items-start gap-3">
                                                            <button
                                                                type="button"
                                                                class="truncate text-left text-xl font-bold leading-tight text-gray-800 transition hover:text-blue-600 dark:text-gray-100 dark:hover:text-blue-400"
                                                                onclick="window.toggleReadyShipmentGroupRows('{{ $group['dom_id'] }}')"
                                                            >
                                                                {{ $group['carrier_name'] }}
                                                            </button>

                                                            <div
                                                                class="flex shrink-0 flex-wrap items-center gap-4 whitespace-nowrap"
                                                            >
                                                                <span
                                                                    class="text-sm font-semibold"
                                                                    style="color: #0f766e;"
                                                                >
                                                                    {{ $group['parcel_count'] }} parcel{{ $group['parcel_count'] > 1 ? 's' : '' }}
                                                                </span>

                                                                <span
                                                                    class="text-sm font-semibold"
                                                                    style="color: #F25022;"
                                                                >
                                                                    {{ core()->formatBasePrice($group['total_cod_amount']) }} COD
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="flex shrink-0 items-center justify-end">
                                                        <button
                                                            type="button"
                                                            data-ready-group-toggle="{{ $group['dom_id'] }}"
                                                            data-ready-group-expanded="false"
                                                            aria-expanded="false"
                                                            class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-300 bg-white text-slate-600 transition hover:border-slate-400 hover:text-slate-800 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-500 dark:hover:text-gray-100"
                                                            onclick="window.toggleReadyShipmentGroupRows('{{ $group['dom_id'] }}')"
                                                        >
                                                            <span
                                                                class="sr-only"
                                                                data-ready-group-toggle-label="{{ $group['dom_id'] }}"
                                                            >
                                                                Expand
                                                            </span>

                                                            <span
                                                                data-ready-group-toggle-icon="{{ $group['dom_id'] }}"
                                                                class="icon-sort-down text-base"
                                                                aria-hidden="true"
                                                            ></span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </x-admin::table.td>
                                        </tr>

                                        @foreach ($group['shipments'] as $shipmentRecord)
                                            <tr
                                                data-ready-child-row="{{ $group['dom_id'] }}"
                                                class="hidden border-t border-slate-200 align-top bg-white dark:border-gray-800 dark:bg-gray-900/40"
                                            >
                                                <x-admin::table.td>
                                                    <input
                                                        type="checkbox"
                                                        name="shipment_record_ids[]"
                                                        value="{{ $shipmentRecord->id }}"
                                                        data-ready-checkbox
                                                        data-ready-group="{{ $group['dom_id'] }}"
                                                        data-carrier-id="{{ $shipmentRecord->shipment_carrier_id }}"
                                                        data-carrier-name="{{ $group['carrier_name'] }}"
                                                        data-cod-amount="{{ (float) $shipmentRecord->cod_amount_expected }}"
                                                        class="h-4 w-4 rounded border-slate-300 text-blue-600"
                                                        @checked($oldSelectedShipmentIds->contains((string) $shipmentRecord->id))
                                                    >
                                                </x-admin::table.td>

                                                <x-admin::table.td class="whitespace-normal">
                                                    <div class="ml-4 border-l-2 border-slate-200 pl-4 dark:border-gray-700">
                                                        <div class="grid gap-1">
                                                            <a
                                                                href="{{ route('admin.sales.orders.view', $shipmentRecord->order_id) }}"
                                                                class="font-semibold text-blue-600 hover:underline"
                                                            >
                                                                #{{ $shipmentRecord->order?->increment_id ?: $shipmentRecord->order_id }}
                                                            </a>

                                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                                Ready for courier handover
                                                            </span>
                                                        </div>
                                                    </div>
                                                </x-admin::table.td>

                                                <x-admin::table.td class="whitespace-normal">
                                                    <div class="grid gap-1">
                                                        <span class="font-semibold text-gray-800 dark:text-gray-100">
                                                            {{ $shipmentRecord->recipient_name ?: $shipmentRecord->order?->customer_full_name ?: 'N/A' }}
                                                        </span>

                                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                                            {{ $shipmentRecord->recipient_phone ?: 'No phone added' }}
                                                        </span>
                                                    </div>
                                                </x-admin::table.td>

                                                <x-admin::table.td class="whitespace-normal">
                                                    <div class="grid gap-1">
                                                        <span>{{ $shipmentRecord->tracking_number ?: 'Not added' }}</span>

                                                        @if ($shipmentRecord->trackingUrl())
                                                            <a
                                                                href="{{ $shipmentRecord->trackingUrl() }}"
                                                                target="_blank"
                                                                rel="noopener noreferrer"
                                                                class="text-xs text-blue-600 hover:underline"
                                                            >
                                                                Open tracking link
                                                            </a>
                                                        @endif
                                                    </div>
                                                </x-admin::table.td>

                                                <x-admin::table.td>
                                                    {{ max(1, (int) $shipmentRecord->package_count) }}
                                                </x-admin::table.td>

                                                <x-admin::table.td>
                                                    @if ((float) $shipmentRecord->cod_amount_expected > 0)
                                                        {{ core()->formatBasePrice((float) $shipmentRecord->cod_amount_expected) }}
                                                    @else
                                                        No COD
                                                    @endif
                                                </x-admin::table.td>

                                                <x-admin::table.td class="whitespace-normal">
                                                    <div class="grid gap-1">
                                                        <span>{{ optional($shipmentRecord->packed_at ?: $shipmentRecord->created_at)->format('d M Y, h:i A') }}</span>

                                                        @if ($shipmentRecord->packer?->name)
                                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                                Packed by {{ $shipmentRecord->packer->name }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </x-admin::table.td>

                                                <x-admin::table.td class="whitespace-normal">
                                                    {{ $shipmentRecord->handover_mode_label ?: 'Not set' }}
                                                </x-admin::table.td>

                                                <x-admin::table.td class="whitespace-normal">
                                                    <div class="grid gap-1 text-sm text-gray-700 dark:text-gray-300">
                                                        @if ($shipmentRecord->handoverBatch?->reference)
                                                            <span class="font-semibold text-gray-800 dark:text-gray-100">
                                                                Batch {{ $shipmentRecord->handoverBatch->reference }}
                                                            </span>
                                                        @endif

                                                        @if ($shipmentRecord->is_fragile || filled($shipmentRecord->special_handling))
                                                            <span>
                                                                {{ $shipmentRecord->is_fragile ? 'Fragile parcel' : 'Special handling' }}
                                                            </span>
                                                        @endif

                                                        @if ($shipmentRecord->internal_note)
                                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                                {{ \Illuminate\Support\Str::limit($shipmentRecord->internal_note, 90) }}
                                                            </span>
                                                        @elseif ($shipmentRecord->courier_note)
                                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                                {{ \Illuminate\Support\Str::limit($shipmentRecord->courier_note, 90) }}
                                                            </span>
                                                        @else
                                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                                Waiting for courier handover confirmation.
                                                            </span>
                                                        @endif
                                                    </div>
                                                </x-admin::table.td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </x-admin::table>
                        </div>

                        <div class="border-t border-slate-200 px-2 pt-2 dark:border-gray-800">
                            {{ $readyShipments->links() }}
                        </div>
                    </form>
                @endif
            </div>
        </section>
    </div>

    <div
        id="handover-batch-modal"
        class="fixed inset-0 z-[10001] hidden items-center justify-center bg-black/60 p-4"
        aria-hidden="true"
    >
        <div
            id="handover-batch-modal-box"
            class="relative z-[10002] flex w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-gray-900"
        >
            <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-6 py-4 dark:border-gray-800">
                <div class="grid gap-1">
                    <p id="handover-batch-modal-title" class="text-lg font-semibold text-gray-800 dark:text-white">
                        Prepare Handover
                    </p>

                    <p id="handover-batch-modal-description" class="text-sm text-gray-600 dark:text-gray-300">
                        Add batch details for the selected parcels.
                    </p>
                </div>

                <button
                    type="button"
                    onclick="window.closeHandoverBatchModal && window.closeHandoverBatchModal()"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-300 text-xl text-gray-600 transition hover:bg-slate-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                    aria-label="Close handover batch modal"
                >
                    ×
                </button>
            </div>

            <div class="grid gap-5 px-6 py-5">
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-gray-800 dark:bg-gray-800/60">
                    <div id="handover-batch-modal-summary" class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                        No parcels selected
                    </div>

                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Batch operations should be prepared for one courier at a time.
                    </div>
                </div>

                @if ($errors->has('selected_shipments'))
                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-100">
                        {{ $errors->first('selected_shipments') }}
                    </div>
                @endif

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="grid gap-1.5">
                        <label for="handover_at" class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                            Handover Date &amp; Time
                        </label>

                        <input
                            id="handover_at"
                            type="datetime-local"
                            name="handover_at"
                            form="handover-batch-form"
                            value="{{ old('handover_at', now()->format('Y-m-d\TH:i')) }}"
                            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 shadow-sm focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                        >

                        @error('handover_at')
                            <p class="text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid gap-1.5">
                        <label for="handover_type" class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                            Handover Type
                        </label>

                        <select
                            id="handover_type"
                            name="handover_type"
                            form="handover-batch-form"
                            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 shadow-sm focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                        >
                            @foreach ($batchHandoverTypes as $handoverTypeValue => $handoverTypeLabel)
                                <option value="{{ $handoverTypeValue }}" @selected(old('handover_type', \Platform\CommerceCore\Models\ShipmentHandoverBatch::TYPE_COURIER_PICKUP) === $handoverTypeValue)>
                                    {{ $handoverTypeLabel }}
                                </option>
                            @endforeach
                        </select>

                        @error('handover_type')
                            <p class="text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid gap-1.5">
                        <label for="receiver_name" class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                            Receiver / Driver Name
                        </label>

                        <input
                            id="receiver_name"
                            type="text"
                            name="receiver_name"
                            form="handover-batch-form"
                            value="{{ old('receiver_name') }}"
                            placeholder="Optional"
                            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 shadow-sm focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                        >

                        @error('receiver_name')
                            <p class="text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid gap-1.5 md:col-span-2">
                        <label for="handover_notes" class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                            Handover Notes
                        </label>

                        <textarea
                            id="handover_notes"
                            name="notes"
                            form="handover-batch-form"
                            rows="3"
                            placeholder="Optional batch note"
                            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 shadow-sm focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                        >{{ old('notes') }}</textarea>

                        @error('notes')
                            <p class="text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-200 px-6 py-4 dark:border-gray-800">
                <button
                    type="button"
                    onclick="window.closeHandoverBatchModal && window.closeHandoverBatchModal()"
                    class="inline-flex rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-slate-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    id="handover-batch-submit-button"
                    form="handover-batch-form"
                    class="primary-button"
                >
                    Continue
                </button>
            </div>
        </div>
    </div>

    <div
        id="shipment-print-preview-modal"
        class="fixed inset-0 z-[10003] hidden items-center justify-center bg-black/60 p-4"
        aria-hidden="true"
    >
        <div
            id="shipment-print-preview-box"
            class="relative z-[10004] flex flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-gray-900"
            style="width: min(1120px, calc(100vw - 2rem)); max-width: min(1120px, calc(100vw - 2rem)); height: min(90vh, 920px);"
        >
            <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-6 py-4 dark:border-gray-800">
                <div class="grid gap-1">
                    <p id="shipment-print-preview-title" class="text-lg font-semibold text-gray-800 dark:text-white">
                        Print Preview
                    </p>

                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Review the document, then print it directly from this preview.
                    </p>
                </div>

                <button
                    type="button"
                    onclick="window.closeShipmentPrintPreview && window.closeShipmentPrintPreview()"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-300 text-xl text-gray-600 transition hover:bg-slate-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                    aria-label="Close print preview"
                >
                    ×
                </button>
            </div>

            <div class="min-h-0 flex-1 bg-slate-100 dark:bg-gray-950">
                <iframe
                    id="shipment-print-preview-frame"
                    title="Shipment print preview"
                    class="h-full w-full border-0 bg-white"
                ></iframe>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-200 px-6 py-4 dark:border-gray-800">
                <button
                    type="button"
                    onclick="window.closeShipmentPrintPreview && window.closeShipmentPrintPreview()"
                    class="inline-flex rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-slate-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                >
                    Close
                </button>

                <button
                    type="button"
                    onclick="window.printShipmentPreview && window.printShipmentPreview()"
                    class="primary-button"
                >
                    Print
                </button>
            </div>
        </div>
    </div>
</x-admin::layouts>

<script>
    window.showShipmentWorkflowWarning = function (message) {
        if (window.app?.config?.globalProperties?.$emitter) {
            window.app.config.globalProperties.$emitter.emit('add-flash', {
                type: 'warning',
                message,
            });

            return;
        }

        window.alert(message);
    };

    window.handoverBatchConfig = {
        draft: {
            title: 'Create Handover Sheet',
            description: 'Add the batch details for the selected parcels, then save a handover sheet for warehouse follow-up.',
            submitLabel: 'Create Handover Sheet',
            formAction: @js(route('admin.sales.to-ship.create-handover-batch')),
            formTarget: '_self',
        },
        print: {
            title: 'Print Manifest',
            description: 'Add the batch details for the selected parcels, then open the handover sheet / manifest in a new tab.',
            submitLabel: 'Print Manifest',
            formAction: @js(route('admin.sales.to-ship.print-manifest')),
            formTarget: '_blank',
        },
        confirm: {
            title: 'Confirm Handover',
            description: 'Add the batch details and confirm these parcels were physically handed over to the courier.',
            submitLabel: 'Confirm Handover',
            formAction: @js(route('admin.sales.to-ship.confirm-handover')),
            formTarget: '_self',
        },
    };

    window.validateShipmentPrintPreviewForm = function (form) {
        const checks = [
            {
                selector: '[name="shipment[carrier_id]"]',
                message: 'Select the courier before opening print preview.',
            },
            {
                selector: '[name="shipment[track_number]"]',
                message: 'Enter the tracking number before opening print preview.',
            },
            {
                selector: '[name="shipment[handover_mode]"]',
                message: 'Choose how this parcel will be handed over before opening print preview.',
            },
        ];

        for (const check of checks) {
            const field = form.querySelector(check.selector);
            const value = typeof field?.value === 'string' ? field.value.trim() : '';

            if (! value) {
                field?.focus?.();
                window.showShipmentWorkflowWarning(check.message);

                return false;
            }
        }

        if (typeof form.reportValidity === 'function' && ! form.reportValidity()) {
            window.showShipmentWorkflowWarning('Complete the required booking fields before opening print preview.');

            return false;
        }

        return true;
    };

    window.readyShipmentMoneyTemplate = @js(core()->formatBasePrice(0));

    window.formatReadyShipmentMoney = function (amount) {
        const template = window.readyShipmentMoneyTemplate || '0.00';
        const numericValue = Number(amount || 0);
        const formattedNumber = numericValue.toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
        const amountPattern = /[-+]?(?:\d{1,3}(?:,\d{3})*|\d+)(?:\.\d+)?/;

        if (! amountPattern.test(template)) {
            return formattedNumber;
        }

        return template.replace(amountPattern, formattedNumber);
    };

    window.selectedReadyShipmentCheckboxes = function () {
        return Array.from(document.querySelectorAll('[data-ready-checkbox]:checked'));
    };

    window.selectedReadyCarrierIds = function () {
        return [...new Set(
            window.selectedReadyShipmentCheckboxes()
                .map((element) => element.dataset.carrierId)
                .filter(Boolean)
        )];
    };

    window.selectedReadyShipmentCodTotal = function () {
        return window.selectedReadyShipmentCheckboxes().reduce((total, element) => {
            return total + Number(element.dataset.codAmount || 0);
        }, 0);
    };

    window.syncReadyShipmentSelectionUi = function () {
        const selected = window.selectedReadyShipmentCheckboxes();
        const summary = document.getElementById('ready-shipment-selection-summary');
        const hint = document.getElementById('ready-shipment-selection-hint');
        const buttons = document.querySelectorAll('[data-handover-bulk-action]');
        const selectAllCheckbox = document.querySelector('[data-ready-select-all]');
        const carrierIds = window.selectedReadyCarrierIds();
        const carrierNames = [...new Set(selected.map((element) => element.dataset.carrierName).filter(Boolean))];
        const selectedCodTotal = window.selectedReadyShipmentCodTotal();

        buttons.forEach((button) => {
            button.disabled = selected.length === 0 || carrierIds.length !== 1;
        });

        if (selectAllCheckbox) {
            const allCheckboxes = document.querySelectorAll('[data-ready-checkbox]');
            selectAllCheckbox.checked = selected.length > 0 && selected.length === allCheckboxes.length;
            selectAllCheckbox.indeterminate = selected.length > 0 && selected.length < allCheckboxes.length;
        }

        document.querySelectorAll('[data-ready-parent-checkbox]').forEach((parentCheckbox) => {
            const groupKey = parentCheckbox.dataset.readyParentCheckbox;
            const groupCheckboxes = Array.from(document.querySelectorAll(`[data-ready-checkbox][data-ready-group="${groupKey}"]`));
            const groupSelected = groupCheckboxes.filter((element) => element.checked);
            const parentSelection = document.querySelector(`[data-ready-parent-selection="${groupKey}"]`);
            const groupTotal = groupCheckboxes.length;

            parentCheckbox.checked = groupSelected.length > 0 && groupSelected.length === groupTotal;
            parentCheckbox.indeterminate = groupSelected.length > 0 && groupSelected.length < groupTotal;

            if (! parentSelection) {
                return;
            }

            if (groupSelected.length === 0) {
                parentSelection.textContent = '0 selected';

                return;
            }

            if (groupSelected.length === groupTotal) {
                parentSelection.textContent = `${groupTotal} selected`;

                return;
            }

            parentSelection.textContent = `${groupSelected.length} of ${groupTotal} selected`;
        });

        if (! summary || ! hint) {
            return;
        }

        if (selected.length === 0) {
            summary.textContent = 'No parcels selected';
            hint.textContent = 'Select parcels from one courier to create a handover sheet or manifest.';
            hint.classList.remove('text-amber-700', 'dark:text-amber-300');
            hint.classList.add('text-gray-500', 'dark:text-gray-400');

            return;
        }

        if (carrierIds.length === 1) {
            const carrierName = carrierNames[0] || 'Selected courier';
            const groupKey = selected[0]?.dataset.readyGroup;
            const groupTotal = groupKey
                ? document.querySelectorAll(`[data-ready-checkbox][data-ready-group="${groupKey}"]`).length
                : selected.length;
            const selectionLabel = selected.length === groupTotal
                ? `${selected.length} parcel${selected.length > 1 ? 's' : ''} selected`
                : `${selected.length} of ${groupTotal} parcels selected`;

            summary.textContent = `${carrierName} — ${selectionLabel} · COD ${window.formatReadyShipmentMoney(selectedCodTotal)}`;
            hint.textContent = `Ready to create one handover sheet for ${carrierName}.`;
            hint.classList.remove('text-amber-700', 'dark:text-amber-300');
            hint.classList.add('text-gray-500', 'dark:text-gray-400');

            return;
        }

        summary.textContent = `${selected.length} parcels selected across ${carrierIds.length} couriers`;
        hint.textContent = 'Select parcels from one courier only to create a handover sheet.';
        hint.classList.remove('text-gray-500', 'dark:text-gray-400');
        hint.classList.add('text-amber-700', 'dark:text-amber-300');
    };

    window.toggleReadyShipmentSelection = function (checked) {
        document.querySelectorAll('[data-ready-checkbox]').forEach((element) => {
            element.checked = checked;
        });

        window.syncReadyShipmentSelectionUi();
    };

    window.toggleReadyShipmentGroupSelection = function (groupKey, checked) {
        if (! groupKey) {
            return;
        }

        document.querySelectorAll(`[data-ready-checkbox][data-ready-group="${groupKey}"]`).forEach((element) => {
            element.checked = checked;
        });

        window.syncReadyShipmentSelectionUi();
    };

    window.toggleReadyShipmentGroupRows = function (groupKey) {
        if (! groupKey) {
            return;
        }

        const toggleButton = document.querySelector(`[data-ready-group-toggle="${groupKey}"]`);
        const toggleLabel = document.querySelector(`[data-ready-group-toggle-label="${groupKey}"]`);
        const toggleIcon = document.querySelector(`[data-ready-group-toggle-icon="${groupKey}"]`);
        const childRows = document.querySelectorAll(`[data-ready-child-row="${groupKey}"]`);

        if (! toggleButton || ! toggleLabel || ! toggleIcon || ! childRows.length) {
            return;
        }

        const isExpanded = toggleButton.dataset.readyGroupExpanded !== 'false';
        const nextExpanded = ! isExpanded;

        childRows.forEach((row) => {
            row.classList.toggle('hidden', ! nextExpanded);
        });

        toggleButton.dataset.readyGroupExpanded = nextExpanded ? 'true' : 'false';
        toggleButton.setAttribute('aria-expanded', nextExpanded ? 'true' : 'false');
        toggleLabel.textContent = nextExpanded ? 'Collapse' : 'Expand';
        toggleIcon.classList.remove('icon-sort-up', 'icon-sort-down');
        toggleIcon.classList.add(nextExpanded ? 'icon-sort-up' : 'icon-sort-down');
    };

    window.openHandoverBatchModal = function (action) {
        const modal = document.getElementById('handover-batch-modal');
        const form = document.getElementById('handover-batch-form');
        const actionInput = document.getElementById('handover_action');
        const titleElement = document.getElementById('handover-batch-modal-title');
        const descriptionElement = document.getElementById('handover-batch-modal-description');
        const summaryElement = document.getElementById('handover-batch-modal-summary');
        const submitButton = document.getElementById('handover-batch-submit-button');
        const selected = window.selectedReadyShipmentCheckboxes();
        const carrierIds = window.selectedReadyCarrierIds();
        const carrierNames = [...new Set(
            selected
                .map((element) => element.dataset.carrierName)
                .filter(Boolean)
        )];
        const config = window.handoverBatchConfig[action] || window.handoverBatchConfig.draft;

        if (! selected.length) {
            window.showShipmentWorkflowWarning('Select at least one parcel from Parcel Ready for Handover.');

            return;
        }

        if (carrierIds.length !== 1) {
            window.showShipmentWorkflowWarning('Select parcels for one courier at a time before preparing a handover.');

            return;
        }

        if (! modal || ! form || ! actionInput || ! titleElement || ! descriptionElement || ! summaryElement || ! submitButton) {
            return;
        }

        actionInput.value = action;
        form.action = config.formAction;
        form.target = config.formTarget;
        titleElement.textContent = config.title;
        descriptionElement.textContent = config.description;
        submitButton.textContent = config.submitLabel;
        summaryElement.textContent = `${carrierNames[0] || 'Selected courier'} — ${selected.length} parcel${selected.length > 1 ? 's' : ''} selected · COD ${window.formatReadyShipmentMoney(window.selectedReadyShipmentCodTotal())}.`;

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
    };

    window.closeHandoverBatchModal = function () {
        const modal = document.getElementById('handover-batch-modal');
        const form = document.getElementById('handover-batch-form');

        if (! modal) {
            return;
        }

        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('aria-hidden', 'true');

        if (form) {
            form.target = '_self';
        }

        document.body.classList.remove('overflow-hidden');
    };

    window.openShipmentPrintPreview = async function (button, actionUrl, title) {
        const form = button.closest('form');

        if (! form) {
            return;
        }

        if (! window.validateShipmentPrintPreviewForm(form)) {
            return;
        }

        const modal = document.getElementById('shipment-print-preview-modal');
        const titleElement = document.getElementById('shipment-print-preview-title');
        const iframe = document.getElementById('shipment-print-preview-frame');

        if (! modal || ! titleElement || ! iframe) {
            return;
        }

        const formData = new FormData(form);

        button.disabled = true;
        button.dataset.originalLabel = button.textContent.trim();
        button.textContent = 'Loading Preview...';

        try {
            const response = await fetch(actionUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html, application/json',
                },
            });

            if (! response.ok) {
                let errorMessage = 'Could not open the print preview. Review the booking fields and try again.';
                const contentType = response.headers.get('content-type') || '';

                if (contentType.includes('application/json')) {
                    const payload = await response.json();

                    errorMessage = payload.message
                        || Object.values(payload.errors || {}).flat()[0]
                        || errorMessage;
                }

                throw new Error(errorMessage);
            }

            const html = await response.text();

            titleElement.textContent = title || 'Print Preview';
            iframe.srcdoc = html;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('overflow-hidden');
        } catch (error) {
            if (window.app?.config?.globalProperties?.$emitter) {
                window.app.config.globalProperties.$emitter.emit('add-flash', {
                    type: 'warning',
                    message: error?.message || 'Could not open the print preview. Review the booking fields and try again.',
                });

                return;
            }

            window.alert(error?.message || 'Could not open the print preview. Review the booking fields and try again.');
        } finally {
            button.disabled = false;
            button.textContent = button.dataset.originalLabel || 'Print Preview';
        }
    };

    window.closeShipmentPrintPreview = function () {
        const modal = document.getElementById('shipment-print-preview-modal');
        const iframe = document.getElementById('shipment-print-preview-frame');

        if (! modal || ! iframe) {
            return;
        }

        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('aria-hidden', 'true');
        iframe.srcdoc = '';
        document.body.classList.remove('overflow-hidden');
    };

    window.printShipmentPreview = function () {
        const iframe = document.getElementById('shipment-print-preview-frame');

        iframe?.contentWindow?.focus();
        iframe?.contentWindow?.print();
    };

    document.addEventListener('click', function (event) {
        const previewButton = event.target.closest('[data-shipment-print-preview]');

        if (previewButton) {
            event.preventDefault();

            window.openShipmentPrintPreview(
                previewButton,
                previewButton.dataset.shipmentPrintPreview,
                previewButton.dataset.printTitle
            );

            return;
        }

        const handoverActionButton = event.target.closest('[data-handover-bulk-action]');

        if (handoverActionButton) {
            event.preventDefault();
            window.openHandoverBatchModal(handoverActionButton.dataset.handoverBulkAction);
        }
    });

    document.addEventListener('change', function (event) {
        if (event.target.matches('[data-ready-checkbox]') || event.target.matches('[data-ready-select-all]')) {
            window.syncReadyShipmentSelectionUi();
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        window.syncReadyShipmentSelectionUi();

        @if ($restoreHandoverModal)
            window.openHandoverBatchModal(@js($restoredHandoverAction));
        @endif
    });
</script>
