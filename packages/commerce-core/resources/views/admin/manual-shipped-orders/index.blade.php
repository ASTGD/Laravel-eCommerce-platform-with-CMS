<x-admin::layouts>
    <x-slot:title>
        In Delivery
    </x-slot>

    <div class="space-y-8 bg-transparent pb-8" style="background-color: #f5f5f5;">
        <section class="flex flex-col gap-4 pt-1 sm:flex-row sm:items-start sm:justify-between">
            <div class="space-y-2">
                <h1 class="text-3xl font-semibold tracking-tight text-slate-950 md:text-4xl dark:text-white">
                    In Delivery
                </h1>

                <p class="max-w-3xl text-sm leading-6 text-slate-500 dark:text-slate-400">
                    Daily follow-up queue for parcels already physically handed over to the courier. Mark delivery here without opening advanced shipment operations.
                </p>
            </div>
        </section>

        <x-commerce-core::admin.basic-list-toolbar
            :paginator="$shipmentRecords"
            search-placeholder="Search shipments"
            :search-value="request('search')"
            :per-page="(int) request('per_page', $shipmentRecords->perPage())"
            :preserve-query="request()->query()"
        >
            <x-slot:filters>
                <div class="grid gap-4 p-4">
                    <p class="text-sm leading-6 text-slate-500 dark:text-slate-400">
                        Search shipments by order number, courier, tracking number, phone, or address.
                    </p>

                    <form method="GET" action="{{ route('admin.sales.shipped-orders.index') }}" class="grid gap-4">
                        <input type="hidden" name="search" value="{{ request('search') }}">
                        <input type="hidden" name="per_page" value="{{ request('per_page', $shipmentRecords->perPage()) }}">
                        <input type="hidden" name="page" value="1">

                        <div class="grid gap-1.5">
                            <label for="carrier_id" class="text-sm font-medium text-slate-700 dark:text-gray-300">
                                Courier
                            </label>

                            <select
                                id="carrier_id"
                                name="carrier_id"
                                class="min-w-[220px] rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-blue-300 focus:ring-2 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200 dark:focus:border-blue-700 dark:focus:ring-blue-950"
                            >
                                <option value="">All couriers</option>

                                @foreach ($carriers as $carrier)
                                    <option value="{{ $carrier->id }}" @selected($selectedCarrierId === $carrier->id)>
                                        {{ $carrier->name }}
                                    </option>
                                @endforeach
                            </select>

                            <p class="text-xs leading-5 text-slate-500 dark:text-slate-400">
                                Use this filter when you need to review one courier's active deliveries only.
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button
                                type="submit"
                                class="inline-flex rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700"
                            >
                                Apply Filter
                            </button>

                            @if ($selectedCarrierId)
                                <a
                                    href="{{ route('admin.sales.shipped-orders.index', array_filter([
                                        'search' => request('search'),
                                        'per_page' => request('per_page'),
                                    ])) }}"
                                    class="inline-flex rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800"
                                >
                                    Clear
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
            </x-slot>
        </x-commerce-core::admin.basic-list-toolbar>

        <div class="overflow-hidden rounded-3xl border border-slate-200/70 bg-white shadow-none dark:border-gray-800 dark:bg-gray-900">
            @if ($shipmentRecords->isEmpty())
                <div class="p-10 text-center text-sm leading-6 text-slate-500 dark:text-slate-400">
                    @if ($selectedCarrierId)
                        No active shipments are in delivery for this courier right now.
                    @else
                        No active shipments are in delivery yet. Confirm a courier handover from To Ship first, and it will appear here.
                    @endif
                </div>
            @else
                <div class="overflow-x-auto">
                    <x-admin::table class="min-w-[1100px]">
                        <thead class="bg-slate-50/80 text-slate-600 dark:bg-slate-950/40 dark:text-slate-300">
                            <tr>
                                <x-admin::table.th>Order</x-admin::table.th>
                                <x-admin::table.th>Customer</x-admin::table.th>
                                <x-admin::table.th>Courier</x-admin::table.th>
                                <x-admin::table.th>Tracking No</x-admin::table.th>
                                <x-admin::table.th>Tracking URL</x-admin::table.th>
                                <x-admin::table.th>Handed Over Date</x-admin::table.th>
                                <x-admin::table.th>COD Amount</x-admin::table.th>
                                <x-admin::table.th>Shipment Status</x-admin::table.th>
                                <x-admin::table.th>Action</x-admin::table.th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($shipmentRecords as $shipmentRecord)
                                <tr class="border-t border-slate-200 align-top dark:border-gray-800">
                                    <x-admin::table.td class="whitespace-normal">
                                        <div class="grid gap-1">
                                            <a
                                                href="{{ route('admin.sales.orders.view', $shipmentRecord->order_id) }}"
                                                class="font-semibold text-blue-600 hover:underline"
                                            >
                                                #{{ $shipmentRecord->order?->increment_id ?: $shipmentRecord->order_id }}
                                            </a>
                                        </div>
                                    </x-admin::table.td>

                                    <x-admin::table.td class="whitespace-normal">
                                        <div class="grid gap-1">
                                            <span class="font-semibold text-slate-950 dark:text-white">
                                                {{ $shipmentRecord->recipient_name ?: $shipmentRecord->order?->customer_full_name ?: 'N/A' }}
                                            </span>

                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $shipmentRecord->recipient_phone ?: $shipmentRecord->order?->customer_email ?: 'No contact info' }}
                                            </span>
                                        </div>
                                    </x-admin::table.td>

                                    <x-admin::table.td class="whitespace-normal">
                                        {{ $shipmentRecord->carrier?->name ?: $shipmentRecord->carrier_name_snapshot ?: 'Manual Courier' }}
                                    </x-admin::table.td>

                                    <x-admin::table.td>
                                        {{ $shipmentRecord->tracking_number ?: 'Not added yet' }}
                                    </x-admin::table.td>

                                    <x-admin::table.td class="whitespace-normal">
                                        @if ($shipmentRecord->trackingUrl())
                                            <a
                                                href="{{ $shipmentRecord->trackingUrl() }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="text-blue-600 hover:underline"
                                            >
                                                Open tracking link
                                            </a>
                                        @else
                                            <span class="text-gray-500 dark:text-gray-400">Not added</span>
                                        @endif
                                    </x-admin::table.td>

                                    <x-admin::table.td class="whitespace-normal">
                                        {{ optional($shipmentRecord->handed_over_at ?: $shipmentRecord->created_at)->format('d M Y, h:i A') ?: 'Not recorded' }}
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
                                            <span class="font-semibold text-slate-950 dark:text-white">
                                                {{ $shipmentRecord->status_label }}
                                            </span>

                                            @if ($shipmentRecord->delivered_at)
                                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                                    Delivered {{ $shipmentRecord->delivered_at->format('d M Y, h:i A') }}
                                                </span>
                                            @endif
                                        </div>
                                    </x-admin::table.td>

                                    <x-admin::table.td class="whitespace-normal">
                                        @php
                                            $deliveryPrompt = sprintf(
                                                'Mark order #%s as delivered? This will move any COD amount into COD Receivables.',
                                                $shipmentRecord->order?->increment_id ?: $shipmentRecord->order_id
                                            );
                                        @endphp

                                        <form
                                            method="POST"
                                            ref="markDeliveredForm{{ $shipmentRecord->id }}"
                                            action="{{ route('admin.sales.shipped-orders.mark-delivered', $shipmentRecord) }}"
                                            class="hidden"
                                        >
                                            @csrf
                                        </form>

                                        <button
                                            type="button"
                                            class="inline-flex rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700"
                                            @click="$emitter.emit('open-confirm-modal', {
                                                title: @js('Confirm Delivery'),
                                                message: @js($deliveryPrompt),
                                                options: {
                                                    btnDisagree: @js('Cancel'),
                                                    btnAgree: @js('Mark Delivered'),
                                                },
                                                agree: () => {
                                                    this.$refs['markDeliveredForm{{ $shipmentRecord->id }}'].submit()
                                                }
                                            })"
                                        >
                                            Mark Delivered
                                        </button>
                                    </x-admin::table.td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-admin::table>
                </div>

                <div class="border-t border-slate-200 px-6 py-4 dark:border-gray-800">
                    {{ $shipmentRecords->links() }}
                </div>
            @endif
        </div>
    </div>
</x-admin::layouts>
