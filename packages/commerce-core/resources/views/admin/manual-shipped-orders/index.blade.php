<x-admin::layouts>
    <x-slot:title>
        Shipped Orders
    </x-slot>

    <div class="grid gap-4">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="grid gap-1">
                    <p class="text-xl font-bold text-gray-900 dark:text-white">
                        Shipped Orders
                    </p>

                    <p class="max-w-3xl text-sm text-gray-600 dark:text-gray-300">
                        This page is the simple manual shipping queue. Use it to review courier details, open the public tracking link, and mark delivered orders without opening the advanced shipment operations tool.
                    </p>
                </div>

                <div class="rounded-lg bg-slate-50 px-4 py-3 text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                    <p class="font-semibold">Manual Basic mode</p>
                    <p>Advanced shipment ops and COD settlement tools stay hidden in this mode.</p>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            @if ($shipmentRecords->isEmpty())
                <div class="p-10 text-center text-sm text-gray-600 dark:text-gray-300">
                    No shipped orders are available yet. Create a shipment from an order first, and it will appear here.
                </div>
            @else
                <div class="overflow-x-auto">
                    <x-admin::table class="min-w-[1100px]">
                        <thead class="bg-slate-50 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                            <tr>
                                <x-admin::table.th>Order</x-admin::table.th>
                                <x-admin::table.th>Customer</x-admin::table.th>
                                <x-admin::table.th>Courier</x-admin::table.th>
                                <x-admin::table.th>Tracking ID</x-admin::table.th>
                                <x-admin::table.th>Tracking Link</x-admin::table.th>
                                <x-admin::table.th>COD Amount</x-admin::table.th>
                                <x-admin::table.th>Status</x-admin::table.th>
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

                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                Registered {{ optional($shipmentRecord->handed_over_at)->format('d M Y, h:i A') ?: 'N/A' }}
                                            </span>
                                        </div>
                                    </x-admin::table.td>

                                    <x-admin::table.td class="whitespace-normal">
                                        <div class="grid gap-1">
                                            <span class="font-semibold text-gray-800 dark:text-gray-100">
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

                                    <x-admin::table.td>
                                        @if ((float) $shipmentRecord->cod_amount_expected > 0)
                                            {{ core()->formatBasePrice((float) $shipmentRecord->cod_amount_expected) }}
                                        @else
                                            No COD
                                        @endif
                                    </x-admin::table.td>

                                    <x-admin::table.td class="whitespace-normal">
                                        <div class="grid gap-1">
                                            <span class="font-semibold text-gray-800 dark:text-gray-100">
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
                                        @if ($shipmentRecord->canBeMarkedDelivered())
                                            <form
                                                method="POST"
                                                action="{{ route('admin.sales.shipped-orders.mark-delivered', $shipmentRecord) }}"
                                                onsubmit="return confirm('Mark this shipment as delivered?');"
                                            >
                                                @csrf

                                                <button
                                                    type="submit"
                                                    class="inline-flex rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700"
                                                >
                                                    Mark Delivered
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs font-semibold uppercase tracking-wide text-emerald-600">
                                                Complete
                                            </span>
                                        @endif
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
