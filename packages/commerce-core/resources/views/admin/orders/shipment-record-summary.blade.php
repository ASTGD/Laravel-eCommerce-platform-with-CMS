@php
    $shipmentRecords = app(\Platform\CommerceCore\Services\ShipmentRecordService::class)->forOrder($order);
    $codSettlements = app(\Platform\CommerceCore\Services\CodSettlementService::class)->forOrder($order);
    $settlementBatches = app(\Platform\CommerceCore\Services\SettlementBatchService::class)->forOrder($order);
@endphp

<x-admin::accordion>
    <x-slot:header>
        <p class="p-2.5 text-base font-semibold text-gray-600 dark:text-gray-300">
            Shipment Ops ({{ $shipmentRecords->count() }})
        </p>
    </x-slot>

    <x-slot:content>
        @forelse ($shipmentRecords as $shipmentRecord)
            <div class="grid gap-y-2.5">
                <div>
                    <p class="font-semibold text-gray-800 dark:text-white">
                        #{{ $shipmentRecord->id }} · {{ $shipmentRecord->status_label }}
                    </p>

                    <p class="text-gray-600 dark:text-gray-300">
                        {{ $shipmentRecord->carrier?->name ?? $shipmentRecord->carrier_name_snapshot ?? 'Carrier pending' }}
                        @if ($shipmentRecord->tracking_number)
                            · {{ $shipmentRecord->tracking_number }}
                        @endif
                    </p>
                </div>

                <div class="flex gap-2.5">
                    <a
                        href="{{ route('admin.sales.shipment-operations.view', $shipmentRecord) }}"
                        class="text-sm text-blue-600 transition-all hover:underline"
                    >
                        View
                    </a>
                </div>
            </div>

            @if (! $loop->last)
                <span class="mb-4 mt-4 block w-full border-b dark:border-gray-800"></span>
            @endif
        @empty
            <p class="text-gray-600 dark:text-gray-300">
                No shipment operations recorded.
            </p>
        @endforelse
    </x-slot>
</x-admin::accordion>

<x-admin::accordion>
    <x-slot:header>
        <p class="p-2.5 text-base font-semibold text-gray-600 dark:text-gray-300">
            COD Settlements ({{ $codSettlements->count() }})
        </p>
    </x-slot>

    <x-slot:content>
        @forelse ($codSettlements as $codSettlement)
            <div class="grid gap-y-2.5">
                <div>
                    <p class="font-semibold text-gray-800 dark:text-white">
                        #{{ $codSettlement->id }} · {{ $codSettlement->status_label }}
                    </p>

                    <p class="text-gray-600 dark:text-gray-300">
                        Expected {{ core()->formatBasePrice($codSettlement->expected_amount) }}
                        · Remitted {{ core()->formatBasePrice($codSettlement->remitted_amount) }}
                        @if ((float) $codSettlement->short_amount > 0)
                            · Short {{ core()->formatBasePrice($codSettlement->short_amount) }}
                        @endif
                    </p>
                </div>

                <div class="flex flex-wrap gap-2.5">
                    <a
                        href="{{ route('admin.sales.cod-settlements.view', $codSettlement) }}"
                        class="text-sm text-blue-600 transition-all hover:underline"
                    >
                        View Settlement
                    </a>

                    @if ($codSettlement->shipmentRecord)
                        <a
                            href="{{ route('admin.sales.shipment-operations.view', $codSettlement->shipmentRecord) }}"
                            class="text-sm text-blue-600 transition-all hover:underline"
                        >
                            Shipment Ops
                        </a>
                    @endif

                    @if ($codSettlement->batchItem?->batch)
                        <a
                            href="{{ route('admin.sales.settlement-batches.view', $codSettlement->batchItem->batch) }}"
                            class="text-sm text-blue-600 transition-all hover:underline"
                        >
                            Settlement Batch
                        </a>
                    @endif
                </div>
            </div>

            @if (! $loop->last)
                <span class="mb-4 mt-4 block w-full border-b dark:border-gray-800"></span>
            @endif
        @empty
            <p class="text-gray-600 dark:text-gray-300">
                No COD settlements recorded.
            </p>
        @endforelse
    </x-slot>
</x-admin::accordion>

<x-admin::accordion>
    <x-slot:header>
        <p class="p-2.5 text-base font-semibold text-gray-600 dark:text-gray-300">
            Settlement Batches ({{ $settlementBatches->count() }})
        </p>
    </x-slot>

    <x-slot:content>
        @forelse ($settlementBatches as $settlementBatch)
            <div class="grid gap-y-2.5">
                <div>
                    <p class="font-semibold text-gray-800 dark:text-white">
                        {{ $settlementBatch->reference }} · {{ $settlementBatch->status_label }}
                    </p>

                    <p class="text-gray-600 dark:text-gray-300">
                        {{ $settlementBatch->carrier?->name ?? 'Carrier pending' }}
                        · {{ $settlementBatch->items->count() }} settlements
                        · Net {{ core()->formatBasePrice($settlementBatch->net_amount) }}
                        @if ((float) $settlementBatch->total_short_amount > 0)
                            · Short {{ core()->formatBasePrice($settlementBatch->total_short_amount) }}
                        @endif
                    </p>
                </div>

                <div class="flex flex-wrap gap-2.5">
                    <a
                        href="{{ route('admin.sales.settlement-batches.view', $settlementBatch) }}"
                        class="text-sm text-blue-600 transition-all hover:underline"
                    >
                        View Batch
                    </a>
                </div>
            </div>

            @if (! $loop->last)
                <span class="mb-4 mt-4 block w-full border-b dark:border-gray-800"></span>
            @endif
        @empty
            <p class="text-gray-600 dark:text-gray-300">
                No settlement batches linked to this order.
            </p>
        @endforelse
    </x-slot>
</x-admin::accordion>
