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
                    Confirmed orders waiting for courier booking. Once booked, they move to In Delivery.
                </p>
            </div>
        </div>

        <x-commerce-core::admin.basic-list-toolbar
            :paginator="$orders"
            search-placeholder="Search orders"
            :search-value="request('search')"
            :per-page="(int) request('per_page', $orders->perPage())"
            :preserve-query="request()->query()"
        >
            <x-slot:filters>
                <div class="grid gap-3 p-4 text-sm text-gray-600 dark:text-gray-300">
                    <p>
                        Search orders by order number, customer, phone, or address.
                    </p>

                    <p>
                        There are no extra filters on this page yet. Use this list to find orders that are ready to book.
                    </p>
                </div>
            </x-slot>
        </x-commerce-core::admin.basic-list-toolbar>

        @if ($shipmentCarriers->isEmpty())
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-100">
                Add at least one active courier service before booking shipments from this page.

                <a
                    href="{{ route('admin.sales.carriers.create') }}"
                    class="ml-1 font-semibold text-blue-600 hover:underline"
                >
                    Add Courier Service
                </a>
            </div>
        @endif

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            @if ($orders->isEmpty())
                <div class="p-10 text-center text-sm text-gray-600 dark:text-gray-300">
                    No orders are waiting for shipment booking right now.
                </div>
            @else
                <div class="overflow-x-auto">
                    <x-admin::table class="min-w-[1280px]">
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
                            @foreach ($orders as $row)
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

                                    <x-admin::table.td>
                                        {{ $row['payment_label'] }}
                                    </x-admin::table.td>

                                    <x-admin::table.td>
                                        {{ $row['order_amount_formatted'] }}
                                    </x-admin::table.td>

                                    <x-admin::table.td class="whitespace-normal">
                                        @if (bouncer()->hasPermission('sales.shipments.create') && $row['can_book'] && $shipmentCarriers->isNotEmpty())
                                            <x-admin::modal
                                                :is-active="$isRestoringModal"
                                                box-style="width: min(900px, calc(100vw - 2rem)); max-width: min(900px, calc(100vw - 2rem));"
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
                                                            Book Shipment
                                                        </p>

                                                        <p class="text-sm font-normal text-gray-600 dark:text-gray-300">
                                                            Order #{{ $order->increment_id }} will move to In Delivery after booking.
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

                                                        @foreach ($row['items_payload'] as $itemId => $sourcePayload)
                                                            @foreach ($sourcePayload as $sourceId => $qty)
                                                                <input
                                                                    type="hidden"
                                                                    name="shipment[items][{{ $itemId }}][{{ $sourceId }}]"
                                                                    value="{{ $qty }}"
                                                                >
                                                            @endforeach
                                                        @endforeach

                                                        <div class="grid gap-4">
                                                            <div class="rounded-lg bg-slate-50 px-4 py-3 text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                                                <p class="font-semibold">Stock source</p>
                                                                <p>{{ $row['inventory_source_name'] }}</p>
                                                            </div>

                                                            <div class="grid gap-4 md:grid-cols-2">
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
                                                                        <option value="">
                                                                            Select courier
                                                                        </option>

                                                                        @foreach ($shipmentCarriers as $shipmentCarrier)
                                                                            <option
                                                                                value="{{ $shipmentCarrier->id }}"
                                                                                @selected($isRestoringModal && (string) old('shipment.carrier_id') === (string) $shipmentCarrier->id)
                                                                            >
                                                                                {{ $shipmentCarrier->name }}
                                                                            </option>
                                                                        @endforeach
                                                                    </x-admin::form.control-group.control>

                                                                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                                                        Choose the courier you are handing this order to.
                                                                    </p>

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
                                                                        Enter the tracking number, consignment ID, or other courier reference the team will use to follow this parcel.
                                                                    </p>

                                                                    <x-admin::form.control-group.error control-name="shipment.track_number" />
                                                                </x-admin::form.control-group>
                                                            </div>

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

                                                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                                                    Optional. Add the direct public tracking link if the courier already gave one to you.
                                                                </p>

                                                                <x-admin::form.control-group.error control-name="shipment.public_tracking_url" />
                                                            </x-admin::form.control-group>

                                                            <x-admin::form.control-group class="!mb-0">
                                                                <x-admin::form.control-group.label>
                                                                    Note
                                                                </x-admin::form.control-group.label>

                                                                <x-admin::form.control-group.control
                                                                    type="textarea"
                                                                    name="shipment[note]"
                                                                    :value="$isRestoringModal ? old('shipment.note') : ''"
                                                                    :label="'Note'"
                                                                    :placeholder="'Optional handover note for your team'"
                                                                />

                                                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                                                    Optional. Use this for courier handover or packaging notes.
                                                                </p>

                                                                <x-admin::form.control-group.error control-name="shipment.note" />
                                                            </x-admin::form.control-group>
                                                        </div>

                                                        <div class="mt-6 flex justify-end">
                                                            <button
                                                                type="submit"
                                                                class="primary-button"
                                                            >
                                                                Save and Move to In Delivery
                                                            </button>
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
                    {{ $orders->links() }}
                </div>
            @endif
        </div>
    </div>
</x-admin::layouts>
