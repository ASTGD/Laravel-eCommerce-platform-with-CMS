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
                                        $bookingFormId = 'booking-form-'.$order->id;
                                        $compactAddressLabel = \Illuminate\Support\Str::limit($row['address_label'], 42);
                                        $paymentSummaryLabel = $row['payment_label'] === 'COD'
                                            ? 'COD '.$row['cod_amount_formatted']
                                            : 'Prepaid';
                                        $itemSummaryLabel = sprintf(
                                            '%d %s • Qty %s',
                                            (int) $row['items_count'],
                                            \Illuminate\Support\Str::plural('item', (int) $row['items_count']),
                                            $row['total_qty']
                                        );
                                        $stockSummaryLabel = $row['stock_check_label'] === 'Ready'
                                            ? 'Stock ready'
                                            : $row['stock_check_label'];
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
                                                    box-style="width: min(980px, calc(100vw - 2rem)); max-width: min(980px, calc(100vw - 2rem));"
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
                                                                Save the parcel booking here, then complete courier handover later from Parcel Ready for Handover.
                                                            </p>
                                                        </div>
                                                    </x-slot>

                                                    <x-slot:content>
                                                        <x-admin::form
                                                            id="{{ $bookingFormId }}"
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
                                                                <section class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-gray-800 dark:bg-gray-800/60">
                                                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                                                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-gray-700 dark:text-gray-200">
                                                                            <span class="font-semibold text-gray-900 dark:text-white">
                                                                                Order #{{ $order->increment_id }}
                                                                            </span>
                                                                            <span class="text-slate-300 dark:text-gray-600">•</span>
                                                                            <span>{{ $row['customer_label'] }}</span>
                                                                            <span class="text-slate-300 dark:text-gray-600">•</span>
                                                                            <span>{{ $compactAddressLabel }}</span>
                                                                            <span class="text-slate-300 dark:text-gray-600">•</span>
                                                                            <span class="{{ $row['payment_label'] === 'COD' ? 'font-semibold text-orange-600 dark:text-orange-400' : '' }}">
                                                                                {{ $paymentSummaryLabel }}
                                                                            </span>
                                                                            <span class="text-slate-300 dark:text-gray-600">•</span>
                                                                            <span>{{ $itemSummaryLabel }}</span>
                                                                            <span class="text-slate-300 dark:text-gray-600">•</span>
                                                                            <span class="font-medium text-emerald-700 dark:text-emerald-400">
                                                                                {{ $stockSummaryLabel }}
                                                                            </span>
                                                                        </div>

                                                                        <a
                                                                            href="{{ route('admin.sales.orders.view', $order->id) }}"
                                                                            target="_blank"
                                                                            rel="noreferrer"
                                                                            class="shrink-0 text-sm font-semibold text-blue-600 hover:underline"
                                                                        >
                                                                            View full order details
                                                                        </a>
                                                                    </div>
                                                                </section>

                                                                <div class="grid gap-5 lg:grid-cols-2">
                                                                    <section class="rounded-xl border border-slate-200 p-4 dark:border-gray-800">
                                                                        <div class="mb-4 grid gap-1">
                                                                            <p class="text-base font-semibold text-gray-800 dark:text-white">
                                                                                Parcel Preparation
                                                                            </p>

                                                                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                                                                Capture how the parcel was packed before courier handover.
                                                                            </p>
                                                                        </div>

                                                                        <div class="grid gap-4">
                                                                            <input
                                                                                type="hidden"
                                                                                name="shipment[stock_checked]"
                                                                                value="{{ $isRestoringModal ? (old('shipment.stock_checked', 1) ? 1 : 0) : 1 }}"
                                                                            >

                                                                            <div class="grid gap-4 sm:grid-cols-2">
                                                                                <div class="grid gap-1 rounded-lg bg-slate-50 px-4 py-3 text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                                                                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Packed By</p>
                                                                                    <p class="font-semibold">{{ $currentAdminName }}</p>
                                                                                    <p class="text-xs text-gray-500 dark:text-gray-400">Packed time is saved automatically.</p>
                                                                                </div>
                                                                            </div>

                                                                            <div class="grid gap-4 sm:grid-cols-2">
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

                                                                                    <x-admin::form.control-group.error control-name="shipment.package_count" />
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
                                                                            </div>

                                                                            <div class="grid gap-4 sm:grid-cols-2">
                                                                                <x-admin::form.control-group class="!mb-0">
                                                                                    <x-admin::form.control-group.label>
                                                                                        Weight (optional)
                                                                                    </x-admin::form.control-group.label>

                                                                                    <x-admin::form.control-group.control
                                                                                        type="number"
                                                                                        name="shipment[package_weight_kg]"
                                                                                        :value="$isRestoringModal ? old('shipment.package_weight_kg') : ''"
                                                                                        :label="'Weight (optional)'"
                                                                                        :placeholder="'0.50'"
                                                                                        step="0.01"
                                                                                        min="0"
                                                                                    />

                                                                                    <x-admin::form.control-group.error control-name="shipment.package_weight_kg" />
                                                                                </x-admin::form.control-group>

                                                                                <x-admin::form.control-group class="!mb-0">
                                                                                    <x-admin::form.control-group.label>
                                                                                        Dimensions (optional)
                                                                                    </x-admin::form.control-group.label>

                                                                                    <x-admin::form.control-group.control
                                                                                        type="text"
                                                                                        name="shipment[package_dimensions]"
                                                                                        :value="$isRestoringModal ? old('shipment.package_dimensions') : ''"
                                                                                        :label="'Dimensions (optional)'"
                                                                                        :placeholder="'12 x 8 x 5 in'"
                                                                                    />

                                                                                    <x-admin::form.control-group.error control-name="shipment.package_dimensions" />
                                                                                </x-admin::form.control-group>
                                                                            </div>

                                                                            <x-admin::form.control-group class="!mb-0">
                                                                                <input type="hidden" name="shipment[is_fragile]" value="0">

                                                                                <label class="inline-flex items-center gap-3 text-sm font-semibold text-gray-800 dark:text-gray-100">
                                                                                    <input
                                                                                        type="checkbox"
                                                                                        name="shipment[is_fragile]"
                                                                                        value="1"
                                                                                        class="h-4 w-4 rounded border-slate-300 text-blue-600"
                                                                                        @checked($isRestoringModal ? (bool) old('shipment.is_fragile') : false)
                                                                                    >

                                                                                    Fragile / special handling needed
                                                                                </label>

                                                                                <x-admin::form.control-group.error control-name="shipment.is_fragile" />
                                                                            </x-admin::form.control-group>

                                                                            <x-admin::form.control-group class="!mb-0">
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

                                                                            <x-admin::form.control-group class="!mb-0">
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
                                                                        <div class="mb-4 grid gap-1">
                                                                            <p class="text-base font-semibold text-gray-800 dark:text-white">
                                                                                Courier Booking
                                                                            </p>

                                                                            <p class="text-sm text-gray-600 dark:text-gray-300">
                                                                                Save the courier details your team will use to follow the parcel.
                                                                            </p>
                                                                        </div>

                                                                        <div class="grid gap-4">
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

                                                                            <x-admin::form.control-group class="!mb-0">
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
                                                                </div>
                                                            </div>
                                                        </x-admin::form>
                                                    </x-slot:content>

                                                    <x-slot:footer>
                                                        <div class="flex w-full flex-wrap items-center justify-end gap-2.5">
                                                            <button
                                                                type="button"
                                                                class="secondary-button"
                                                                style="background-color: #f65d35; border-color: #f65d35; color: #ffffff;"
                                                                onclick="this.closest('.box-shadow')?.querySelector('.icon-cancel-1')?.click()"
                                                            >
                                                                Cancel
                                                            </button>

                                                            <button
                                                                type="submit"
                                                                form="{{ $bookingFormId }}"
                                                                class="primary-button"
                                                            >
                                                                Save Booking
                                                            </button>

                                                            <button
                                                                type="button"
                                                                data-shipment-print-preview="{{ route('admin.sales.to-ship.print-documents', [$order, 'document' => 'label']) }}"
                                                                data-shipment-form-id="{{ $bookingFormId }}"
                                                                data-print-title="Parcel Label Preview"
                                                                class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90"
                                                                style="background-color: #7fba00;"
                                                            >
                                                                Print Label
                                                            </button>

                                                            <button
                                                                type="button"
                                                                data-shipment-print-preview="{{ route('admin.sales.to-ship.print-documents', [$order, 'document' => 'invoice']) }}"
                                                                data-shipment-form-id="{{ $bookingFormId }}"
                                                                data-print-title="Invoice Preview"
                                                                class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold text-gray-900 transition hover:opacity-90"
                                                                style="background-color: #ffba07;"
                                                            >
                                                                Print Invoice
                                                            </button>

                                                            <button
                                                                type="button"
                                                                data-shipment-print-preview="{{ route('admin.sales.to-ship.print-documents', [$order, 'document' => 'both']) }}"
                                                                data-shipment-form-id="{{ $bookingFormId }}"
                                                                data-print-title="Parcel Label and Invoice Preview"
                                                                class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-slate-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                                                            >
                                                                Print Both
                                                            </button>
                                                        </div>
                                                    </x-slot:footer>
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
                    <div class="grid gap-4 p-4">
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
                                        Select parcels from one courier to prepare its handover sheet.
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    <button
                                        type="button"
                                        data-handover-bulk-action="prepare"
                                        class="inline-flex rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                                        disabled
                                    >
                                        Create/Print Handover Sheet
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

                        @if ($errors->has('selected_shipments'))
                            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-100">
                                {{ $errors->first('selected_shipments') }}
                            </div>
                        @endif

                        <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-gray-800">
                            <x-admin::table class="min-w-[1320px]">
                                <thead class="bg-white text-gray-800 dark:bg-gray-900 dark:text-gray-100">
                                    <tr>
                                        <x-admin::table.th class="w-[56px]"></x-admin::table.th>
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
                                        @php($activeReadyBatch = $shipmentRecord->handoverBatch && ! $shipmentRecord->handoverBatch->confirmed_at ? $shipmentRecord->handoverBatch : null)
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
                                                        data-ready-batch-id="{{ (string) ($activeReadyBatch?->id ?: '') }}"
                                                        data-ready-batch-reference="{{ (string) ($activeReadyBatch?->reference ?: '') }}"
                                                        data-ready-batch-shipment-count="{{ (int) ($activeReadyBatch?->shipments_count ?: 0) }}"
                                                        data-ready-batch-handover-at="{{ $activeReadyBatch?->handover_at?->format('Y-m-d\\TH:i') ?: '' }}"
                                                        data-ready-batch-handover-type="{{ (string) ($activeReadyBatch?->handover_type ?: '') }}"
                                                        data-ready-batch-receiver-name="{{ (string) ($activeReadyBatch?->receiver_name ?: '') }}"
                                                        data-ready-batch-notes="{{ (string) ($activeReadyBatch?->notes ?: '') }}"
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
                                                        @if ($activeReadyBatch?->reference)
                                                            <span class="font-semibold text-gray-800 dark:text-gray-100">
                                                                Batch {{ $activeReadyBatch->reference }}
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
                    </div>
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
            class="relative z-[10002] flex w-full flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-gray-900"
            style="max-width: 820px; max-height: calc(100vh - 3rem);"
        >
            <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-6 py-4 dark:border-gray-800">
                <div class="grid gap-1">
                    <p id="handover-batch-modal-title" class="text-lg font-semibold text-gray-800 dark:text-white">
                        Prepare Handover Sheet
                    </p>

                    <p id="handover-batch-modal-description" class="text-sm text-gray-600 dark:text-gray-300">
                        Add the courier handover details, then generate the handover sheet preview.
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

            <div class="grid gap-5 overflow-y-auto px-6 py-5">
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4 dark:border-gray-800 dark:bg-gray-800/60">
                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Courier</div>
                            <div id="handover-batch-modal-courier" class="mt-1 text-sm font-semibold text-gray-800 dark:text-gray-100">Not selected</div>
                        </div>

                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Selected Parcels</div>
                            <div id="handover-batch-modal-parcels" class="mt-1 text-sm font-semibold text-gray-800 dark:text-gray-100">0</div>
                        </div>

                        <div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Total COD Amount</div>
                            <div id="handover-batch-modal-cod" class="mt-1 text-sm font-semibold text-gray-800 dark:text-gray-100">{{ core()->formatBasePrice(0) }}</div>
                        </div>
                    </div>
                </div>

                <div
                    id="handover-batch-modal-error"
                    class="hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/20 dark:text-red-200"
                ></div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="grid gap-1.5">
                        <label for="handover_at" class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                            Handover Date &amp; Time
                        </label>

                        <input
                            id="handover_at"
                            type="datetime-local"
                            value="{{ now()->format('Y-m-d\TH:i') }}"
                            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 shadow-sm focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                        >

                        <p class="hidden text-xs font-medium text-red-600 dark:text-red-400" data-handover-error-for="handover_at"></p>
                    </div>

                    <div class="grid gap-1.5">
                        <label for="handover_type" class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                            Handover Type
                        </label>

                        <select
                            id="handover_type"
                            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 shadow-sm focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                        >
                            @foreach ($batchHandoverTypes as $handoverTypeValue => $handoverTypeLabel)
                                <option value="{{ $handoverTypeValue }}" @selected(\Platform\CommerceCore\Models\ShipmentHandoverBatch::TYPE_COURIER_PICKUP === $handoverTypeValue)>
                                    {{ $handoverTypeLabel }}
                                </option>
                            @endforeach
                        </select>

                        <p class="hidden text-xs font-medium text-red-600 dark:text-red-400" data-handover-error-for="handover_type"></p>
                    </div>

                    <div class="grid gap-1.5 md:col-span-2">
                        <label for="receiver_name" class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                            Receiver / Driver Name
                        </label>

                        <input
                            id="receiver_name"
                            type="text"
                            placeholder="Optional"
                            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 shadow-sm focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                        >

                        <p class="hidden text-xs font-medium text-red-600 dark:text-red-400" data-handover-error-for="receiver_name"></p>
                    </div>

                    <div class="grid gap-1.5 md:col-span-2">
                        <label for="handover_notes" class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                            Handover Notes
                        </label>

                        <textarea
                            id="handover_notes"
                            rows="3"
                            placeholder="Optional batch note"
                            class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-gray-800 shadow-sm focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                        ></textarea>

                        <p class="hidden text-xs font-medium text-red-600 dark:text-red-400" data-handover-error-for="notes"></p>
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
                    type="button"
                    id="handover-batch-submit-button"
                    class="primary-button"
                >
                    Generate Handover Sheet
                </button>
            </div>
        </div>
    </div>

    <div
        id="handover-confirm-modal"
        class="fixed inset-0 z-[10001] hidden items-center justify-center bg-black/60 p-4"
        aria-hidden="true"
    >
        <div
            class="relative z-[10002] flex w-full flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-gray-900"
            style="width: min(760px, calc(100vw - 2rem)); max-width: min(760px, calc(100vw - 2rem));"
        >
            <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-6 py-4 dark:border-gray-800">
                <div class="grid gap-1">
                    <p class="text-lg font-semibold text-gray-800 dark:text-white">Confirm Handover</p>
                    <p class="text-sm text-gray-600 dark:text-gray-300">Use this only after the courier has physically received the selected parcels.</p>
                </div>

                <button
                    type="button"
                    onclick="window.closeHandoverConfirmModal && window.closeHandoverConfirmModal()"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-300 text-xl text-gray-600 transition hover:bg-slate-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                    aria-label="Close confirm handover modal"
                >
                    ×
                </button>
            </div>

            <div class="grid gap-4 px-6 py-5">
                <div id="handover-confirm-modal-summary" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm font-semibold text-gray-800 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-100">
                    No parcels selected
                </div>

                <p class="text-sm text-gray-600 dark:text-gray-300">
                    Confirming handover will move the selected parcels from Parcel Ready for Handover to In Delivery.
                </p>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-200 px-6 py-4 dark:border-gray-800">
                <button
                    type="button"
                    onclick="window.closeHandoverConfirmModal && window.closeHandoverConfirmModal()"
                    class="inline-flex rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-slate-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                >
                    Cancel
                </button>

                <button
                    type="button"
                    id="handover-confirm-submit-button"
                    class="primary-button"
                >
                    Confirm Handover
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

    window.prepareHandoverSheetUrl = @js(route('admin.sales.to-ship.create-handover-batch'));
    window.confirmPreparedHandoverUrl = @js(route('admin.sales.to-ship.confirm-handover'));
    window.handoverSheetPreviewUrlTemplate = @js(route('admin.sales.to-ship.handover-sheet.preview', ['handoverBatch' => '__BATCH__']));
    window.csrfToken = @js(csrf_token());

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

    window.currentReadyShipmentSelectionState = function () {
        const selected = window.selectedReadyShipmentCheckboxes();
        const carrierIds = window.selectedReadyCarrierIds();
        const carrierNames = [...new Set(selected.map((element) => element.dataset.carrierName).filter(Boolean))];
        const carrierName = carrierNames[0] || 'Selected courier';
        const selectedCodTotal = window.selectedReadyShipmentCodTotal();
        const groupKey = selected[0]?.dataset.readyGroup;
        const groupTotal = groupKey
            ? document.querySelectorAll(`[data-ready-checkbox][data-ready-group="${groupKey}"]`).length
            : selected.length;
        const selectionLabel = selected.length === groupTotal
            ? `${selected.length} parcel${selected.length > 1 ? 's' : ''} selected`
            : `${selected.length} of ${groupTotal} parcels selected`;

        if (! selected.length) {
            return {
                selected,
                carrierIds,
                carrierName: null,
                selectedCodTotal,
                summaryText: 'No parcels selected',
                hintText: 'Select parcels under one courier to prepare the handover sheet.',
                hintTone: 'muted',
                canPrepare: false,
                canConfirm: false,
                preparedBatch: null,
            };
        }

        if (carrierIds.length !== 1) {
            return {
                selected,
                carrierIds,
                carrierName: null,
                selectedCodTotal,
                summaryText: `${selected.length} parcels selected across ${carrierIds.length} couriers`,
                hintText: 'Select parcels from one courier only.',
                hintTone: 'warning',
                canPrepare: false,
                canConfirm: false,
                preparedBatch: null,
            };
        }

        const batchIds = [...new Set(selected.map((element) => element.dataset.readyBatchId).filter(Boolean))];
        const batchReferences = [...new Set(selected.map((element) => element.dataset.readyBatchReference).filter(Boolean))];
        const batchShipmentCounts = [...new Set(
            selected
                .map((element) => Number(element.dataset.readyBatchShipmentCount || 0))
                .filter((count) => count > 0)
        )];
        const isPreparedSelection = batchIds.length === 1
            && batchReferences.length === 1
            && batchShipmentCounts.length === 1
            && batchShipmentCounts[0] === selected.length
            && selected.every((element) => element.dataset.readyBatchId === batchIds[0]);

        if (isPreparedSelection) {
            return {
                selected,
                carrierIds,
                carrierName,
                selectedCodTotal,
                summaryText: `Handover sheet ready for ${carrierName} — ${selected.length} parcel${selected.length > 1 ? 's' : ''}`,
                hintText: `Batch ${batchReferences[0]} is ready. You can print it again or confirm handover.`,
                hintTone: 'muted',
                canPrepare: true,
                canConfirm: true,
                preparedBatch: {
                    id: batchIds[0],
                    reference: batchReferences[0],
                    shipmentCount: batchShipmentCounts[0],
                },
            };
        }

        return {
            selected,
            carrierIds,
            carrierName,
            selectedCodTotal,
            summaryText: `${carrierName} — ${selectionLabel} · COD ${window.formatReadyShipmentMoney(selectedCodTotal)}`,
            hintText: 'Prepare the handover sheet before confirming handover.',
            hintTone: 'muted',
            canPrepare: true,
            canConfirm: false,
            preparedBatch: null,
        };
    };

    window.syncReadyShipmentSelectionUi = function () {
        const state = window.currentReadyShipmentSelectionState();
        const summary = document.getElementById('ready-shipment-selection-summary');
        const hint = document.getElementById('ready-shipment-selection-hint');
        const prepareButton = document.querySelector('[data-handover-bulk-action="prepare"]');
        const confirmButton = document.querySelector('[data-handover-bulk-action="confirm"]');

        if (prepareButton) {
            prepareButton.disabled = ! state.canPrepare;
            prepareButton.textContent = state.preparedBatch
                ? 'View Handover Sheet'
                : 'Create/Print Handover Sheet';
        }

        if (confirmButton) {
            confirmButton.disabled = ! state.canConfirm;
        }

        document.querySelectorAll('[data-ready-parent-checkbox]').forEach((parentCheckbox) => {
            const groupKey = parentCheckbox.dataset.readyParentCheckbox;
            const groupCheckboxes = Array.from(document.querySelectorAll(`[data-ready-checkbox][data-ready-group="${groupKey}"]`));
            const groupSelected = groupCheckboxes.filter((element) => element.checked);
            const groupTotal = groupCheckboxes.length;

            parentCheckbox.checked = groupSelected.length > 0 && groupSelected.length === groupTotal;
            parentCheckbox.indeterminate = groupSelected.length > 0 && groupSelected.length < groupTotal;
        });

        if (! summary || ! hint) {
            return;
        }

        summary.textContent = state.summaryText;
        hint.textContent = state.hintText;
        hint.classList.remove('text-amber-700', 'dark:text-amber-300', 'text-gray-500', 'dark:text-gray-400');

        if (state.hintTone === 'warning') {
            hint.classList.add('text-amber-700', 'dark:text-amber-300');

            return;
        }

        hint.classList.add('text-gray-500', 'dark:text-gray-400');
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

    window.clearHandoverBatchModalErrors = function () {
        const errorBox = document.getElementById('handover-batch-modal-error');

        if (errorBox) {
            errorBox.textContent = '';
            errorBox.classList.add('hidden');
        }

        document.querySelectorAll('[data-handover-error-for]').forEach((element) => {
            element.textContent = '';
            element.classList.add('hidden');
        });
    };

    window.setHandoverBatchModalError = function (key, message) {
        if (! key) {
            const errorBox = document.getElementById('handover-batch-modal-error');

            if (! errorBox) {
                return;
            }

            errorBox.textContent = message;
            errorBox.classList.remove('hidden');

            return;
        }

        const fieldError = document.querySelector(`[data-handover-error-for="${key}"]`);

        if (! fieldError) {
            window.setHandoverBatchModalError('', message);

            return;
        }

        fieldError.textContent = message;
        fieldError.classList.remove('hidden');
    };

    window.openHandoverBatchModal = function () {
        const modal = document.getElementById('handover-batch-modal');
        const titleElement = document.getElementById('handover-batch-modal-title');
        const descriptionElement = document.getElementById('handover-batch-modal-description');
        const courierElement = document.getElementById('handover-batch-modal-courier');
        const parcelElement = document.getElementById('handover-batch-modal-parcels');
        const codElement = document.getElementById('handover-batch-modal-cod');
        const submitButton = document.getElementById('handover-batch-submit-button');
        const handoverAtField = document.getElementById('handover_at');
        const handoverTypeField = document.getElementById('handover_type');
        const receiverField = document.getElementById('receiver_name');
        const notesField = document.getElementById('handover_notes');
        const state = window.currentReadyShipmentSelectionState();

        if (! state.canPrepare) {
            window.showShipmentWorkflowWarning(state.hintText);

            return;
        }

        if (! modal || ! titleElement || ! descriptionElement || ! courierElement || ! parcelElement || ! codElement || ! submitButton || ! handoverAtField || ! handoverTypeField || ! receiverField || ! notesField) {
            return;
        }

        window.clearHandoverBatchModalErrors();
        titleElement.textContent = 'Prepare Handover Sheet';
        descriptionElement.textContent = 'Add the courier handover details, then generate the handover sheet preview.';
        courierElement.textContent = state.carrierName || 'Selected courier';
        parcelElement.textContent = `${state.selected.length} parcel${state.selected.length > 1 ? 's' : ''}`;
        codElement.textContent = window.formatReadyShipmentMoney(state.selectedCodTotal);
        submitButton.textContent = 'Generate Handover Sheet';

        if (state.preparedBatch) {
            const firstSelected = state.selected[0];

            handoverAtField.value = firstSelected?.dataset.readyBatchHandoverAt || handoverAtField.value;
            handoverTypeField.value = firstSelected?.dataset.readyBatchHandoverType || handoverTypeField.value;
            receiverField.value = firstSelected?.dataset.readyBatchReceiverName || '';
            notesField.value = firstSelected?.dataset.readyBatchNotes || '';
        } else {
            handoverAtField.value = handoverAtField.value || @js(now()->format('Y-m-d\TH:i'));
            handoverTypeField.value = handoverTypeField.value || @js(\Platform\CommerceCore\Models\ShipmentHandoverBatch::TYPE_COURIER_PICKUP);
            receiverField.value = '';
            notesField.value = '';
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
    };

    window.openPreparedHandoverSheetPreview = function (batchId) {
        if (! batchId || ! window.handoverSheetPreviewUrlTemplate) {
            return;
        }

        window.open(
            window.handoverSheetPreviewUrlTemplate.replace('__BATCH__', encodeURIComponent(String(batchId))),
            '_blank'
        );
    };

    window.closeHandoverBatchModal = function () {
        const modal = document.getElementById('handover-batch-modal');

        if (! modal) {
            return;
        }

        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('aria-hidden', 'true');

        document.body.classList.remove('overflow-hidden');
    };

    window.applyPreparedBatchToSelection = function (selected, batch, previousBatchIds = []) {
        const batchShipmentIds = new Set((batch.shipment_record_ids || []).map((id) => String(id)));

        document.querySelectorAll('[data-ready-checkbox]').forEach((element) => {
            if (previousBatchIds.includes(element.dataset.readyBatchId) && ! batchShipmentIds.has(element.value)) {
                element.dataset.readyBatchId = '';
                element.dataset.readyBatchReference = '';
                element.dataset.readyBatchShipmentCount = '0';
                element.dataset.readyBatchHandoverAt = '';
                element.dataset.readyBatchHandoverType = '';
                element.dataset.readyBatchReceiverName = '';
                element.dataset.readyBatchNotes = '';
            }

            if (! batchShipmentIds.has(element.value)) {
                return;
            }

            element.dataset.readyBatchId = String(batch.id);
            element.dataset.readyBatchReference = batch.reference || '';
            element.dataset.readyBatchShipmentCount = String(batch.shipment_count || batch.shipment_record_ids?.length || 0);
            element.dataset.readyBatchHandoverAt = batch.handover_at || '';
            element.dataset.readyBatchHandoverType = batch.handover_type || '';
            element.dataset.readyBatchReceiverName = batch.receiver_name || '';
            element.dataset.readyBatchNotes = batch.notes || '';
            element.checked = true;
        });
    };

    window.generateHandoverSheet = async function () {
        const state = window.currentReadyShipmentSelectionState();
        const previewWindow = window.open('', '_blank');

        if (! state.canPrepare) {
            previewWindow?.close?.();
            window.showShipmentWorkflowWarning(state.hintText);

            return;
        }

        const handoverAtField = document.getElementById('handover_at');
        const handoverTypeField = document.getElementById('handover_type');
        const receiverField = document.getElementById('receiver_name');
        const notesField = document.getElementById('handover_notes');
        const submitButton = document.getElementById('handover-batch-submit-button');
        const previousBatchIds = [...new Set(state.selected.map((element) => element.dataset.readyBatchId).filter(Boolean))];

        if (! handoverAtField?.value) {
            previewWindow?.close?.();
            window.setHandoverBatchModalError('handover_at', 'Choose the handover date and time.');
            handoverAtField?.focus?.();

            return;
        }

        if (! handoverTypeField?.value) {
            previewWindow?.close?.();
            window.setHandoverBatchModalError('handover_type', 'Choose how these parcels are being handed over.');
            handoverTypeField?.focus?.();

            return;
        }

        window.clearHandoverBatchModalErrors();

        const payload = new FormData();
        state.selected.forEach((element) => {
            payload.append('shipment_record_ids[]', element.value);
        });
        payload.append('_token', window.csrfToken);
        payload.append('handover_at', handoverAtField.value);
        payload.append('handover_type', handoverTypeField.value);
        payload.append('receiver_name', receiverField?.value || '');
        payload.append('notes', notesField?.value || '');

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.dataset.originalLabel = submitButton.textContent.trim();
            submitButton.textContent = 'Generating...';
        }

        try {
            const response = await fetch(window.prepareHandoverSheetUrl, {
                method: 'POST',
                body: payload,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            const contentType = response.headers.get('content-type') || '';
            const data = contentType.includes('application/json')
                ? await response.json()
                : null;

            if (! response.ok) {
                previewWindow?.close?.();

                if (response.status === 422 && data) {
                    const errors = data.errors || {};

                    Object.entries(errors).forEach(([key, messages]) => {
                        const fieldKey = key.replace(/^shipment_record_ids(?:\.\d+)?$/, 'selected_shipments');
                        window.setHandoverBatchModalError(fieldKey, Array.isArray(messages) ? messages[0] : messages);
                    });

                    if (data.message && ! Object.keys(errors).length) {
                        window.setHandoverBatchModalError('', data.message);
                    }

                    return;
                }

                throw new Error(data?.message || 'Could not generate the handover sheet preview.');
            }

            window.applyPreparedBatchToSelection(state.selected, {
                ...data.batch,
                handover_at: handoverAtField.value,
                handover_type: handoverTypeField.value,
                receiver_name: receiverField?.value || '',
                notes: notesField?.value || '',
            }, previousBatchIds);

            window.closeHandoverBatchModal();
            window.syncReadyShipmentSelectionUi();

            if (previewWindow) {
                previewWindow.location = data.preview_url;
            } else {
                window.open(data.preview_url, '_blank');
            }
        } catch (error) {
            previewWindow?.close?.();
            window.setHandoverBatchModalError('', error?.message || 'Could not generate the handover sheet preview.');
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.textContent = submitButton.dataset.originalLabel || 'Generate Handover Sheet';
            }
        }
    };

    window.openHandoverConfirmModal = function () {
        const modal = document.getElementById('handover-confirm-modal');
        const summaryElement = document.getElementById('handover-confirm-modal-summary');
        const state = window.currentReadyShipmentSelectionState();

        if (! state.canConfirm) {
            window.showShipmentWorkflowWarning(state.hintText);

            return;
        }

        if (! modal || ! summaryElement) {
            return;
        }

        summaryElement.textContent = `${state.carrierName} — ${state.selected.length} parcel${state.selected.length > 1 ? 's' : ''} · Batch ${state.preparedBatch.reference}`;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
    };

    window.closeHandoverConfirmModal = function () {
        const modal = document.getElementById('handover-confirm-modal');

        if (! modal) {
            return;
        }

        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('overflow-hidden');
    };

    window.submitConfirmedHandover = function () {
        const state = window.currentReadyShipmentSelectionState();

        if (! state.canConfirm) {
            window.showShipmentWorkflowWarning(state.hintText);

            return;
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = window.confirmPreparedHandoverUrl;

        const tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = '_token';
        tokenInput.value = window.csrfToken;
        form.appendChild(tokenInput);

        state.selected.forEach((element) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'shipment_record_ids[]';
            input.value = element.value;
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
    };

    window.openShipmentPrintPreview = async function (button, actionUrl, title) {
        const form = button.closest('form')
            || (button.dataset.shipmentFormId ? document.getElementById(button.dataset.shipmentFormId) : null);

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
            const action = handoverActionButton.dataset.handoverBulkAction;

            if (action === 'prepare') {
                const state = window.currentReadyShipmentSelectionState();

                if (state.preparedBatch?.id) {
                    window.openPreparedHandoverSheetPreview(state.preparedBatch.id);

                    return;
                }

                window.openHandoverBatchModal();

                return;
            }

            if (action === 'confirm') {
                window.openHandoverConfirmModal();
            }

            return;
        }

        const handoverGenerateButton = event.target.closest('#handover-batch-submit-button');

        if (handoverGenerateButton) {
            event.preventDefault();
            window.generateHandoverSheet();

            return;
        }

        const handoverConfirmButton = event.target.closest('#handover-confirm-submit-button');

        if (handoverConfirmButton) {
            event.preventDefault();
            window.submitConfirmedHandover();
        }
    });

    document.addEventListener('change', function (event) {
        if (event.target.matches('[data-ready-checkbox]')) {
            window.syncReadyShipmentSelectionUi();
        }
    });

    window.initializeReadyShipmentUi = function () {
        window.syncReadyShipmentSelectionUi();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', window.initializeReadyShipmentUi, { once: true });
    } else {
        window.initializeReadyShipmentUi();
    }
</script>
