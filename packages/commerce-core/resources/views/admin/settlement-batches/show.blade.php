<x-admin::layouts>
    <x-slot:title>
        Settlement Batch #{{ $settlementBatch->id }}
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            Settlement Batch #{{ $settlementBatch->id }}
        </p>

        <div class="flex items-center gap-x-2.5">
            <a
                href="{{ route('admin.sales.settlement-batches.index') }}"
                class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
            >
                Back
            </a>
        </div>
    </div>

    <div class="mt-3.5 flex gap-2.5 max-xl:flex-wrap">
        <div class="flex flex-1 flex-col gap-2 max-xl:flex-auto">
            <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                <div class="flex items-start justify-between gap-4 max-md:flex-wrap">
                    <div>
                        <p class="text-base font-semibold text-gray-800 dark:text-white">
                            Batch Summary
                        </p>

                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                            {{ $settlementBatch->status_label }}
                        </p>
                    </div>

                    @if (bouncer()->hasPermission('sales.settlement_batches.update'))
                        <form
                            method="POST"
                            action="{{ route('admin.sales.settlement-batches.update', $settlementBatch) }}"
                            class="grid gap-2 md:min-w-[340px]"
                        >
                            @csrf

                            <input
                                type="text"
                                name="reference"
                                value="{{ old('reference', $settlementBatch->reference) }}"
                                class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                                placeholder="Batch reference"
                            />

                            <input
                                type="text"
                                name="payout_method"
                                value="{{ old('payout_method', $settlementBatch->payout_method) }}"
                                class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                                placeholder="Payout method"
                            />

                            <select
                                name="status"
                                class="custom-select w-full rounded-md border bg-white px-3 py-2.5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                            >
                                @foreach ($statusOptions as $status => $label)
                                    <option
                                        value="{{ $status }}"
                                        @selected(old('status', $settlementBatch->status) === $status)
                                    >
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>

                            <textarea
                                name="notes"
                                rows="2"
                                class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                                placeholder="Optional notes"
                            >{{ old('notes', $settlementBatch->notes) }}</textarea>

                            <button
                                type="submit"
                                class="primary-button"
                            >
                                Update Batch
                            </button>
                        </form>
                    @endif
                </div>

                @if ($summary['requires_attention'])
                    <div class="mt-4 rounded border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100">
                        <p class="font-semibold">
                            Reconciliation Exceptions Present
                        </p>

                        <p class="mt-1">
                            {{ $summary['short_count'] }} short item(s),
                            {{ $summary['disputed_count'] }} disputed item(s),
                            {{ $summary['written_off_count'] }} written-off item(s).
                        </p>
                    </div>
                @endif

                <div class="mt-4 grid grid-cols-2 gap-4 max-md:grid-cols-1">
                    <div>
                        <p class="font-semibold text-gray-800 dark:text-white">
                            Reference
                        </p>

                        <p class="text-gray-600 dark:text-gray-300">
                            {{ $settlementBatch->reference }}
                        </p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-800 dark:text-white">
                            Carrier
                        </p>

                        <p class="text-gray-600 dark:text-gray-300">
                            {{ $settlementBatch->carrier?->name ?? 'N/A' }}
                        </p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-800 dark:text-white">
                            Payout Method
                        </p>

                        <p class="text-gray-600 dark:text-gray-300">
                            {{ $settlementBatch->payout_method ? str($settlementBatch->payout_method)->replace('_', ' ')->title() : 'N/A' }}
                        </p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-800 dark:text-white">
                            Settlements
                        </p>

                        <p class="text-gray-600 dark:text-gray-300">
                            {{ $settlementBatch->items->count() }}
                        </p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-800 dark:text-white">
                            Remitted
                        </p>

                        <p class="text-gray-600 dark:text-gray-300">
                            {{ $settlementBatch->remitted_at?->format('d M, Y h:i a') ?? 'N/A' }}
                        </p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-800 dark:text-white">
                            Received
                        </p>

                        <p class="text-gray-600 dark:text-gray-300">
                            {{ $settlementBatch->received_at?->format('d M, Y h:i a') ?? 'N/A' }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                    Batch Items
                </p>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px] table-auto border-separate border-spacing-y-2">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wide text-gray-500">
                                <th class="px-2 py-1">Order</th>
                                <th class="px-2 py-1">Settlement</th>
                                <th class="px-2 py-1">Shipment</th>
                                <th class="px-2 py-1">Expected</th>
                                <th class="px-2 py-1">Remitted</th>
                                <th class="px-2 py-1">Adjustment</th>
                                <th class="px-2 py-1">Short</th>
                                <th class="px-2 py-1">Status</th>
                                <th class="px-2 py-1">Note</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($settlementBatch->items as $item)
                                <tr class="rounded border {{ ($item->codSettlement?->requires_attention ?? false) ? 'border-amber-300 bg-amber-50/60 dark:border-amber-800 dark:bg-amber-950/20' : 'border-gray-200 dark:border-gray-800' }} align-top">
                                    <td class="px-2 py-2 text-sm text-gray-700 dark:text-gray-300">
                                        {{ $item->codSettlement?->order?->increment_id ?? 'N/A' }}
                                    </td>
                                    <td class="px-2 py-2 text-sm text-gray-700 dark:text-gray-300">
                                        <a
                                            href="{{ route('admin.sales.cod-settlements.view', $item->cod_settlement_id) }}"
                                            class="text-blue-600 transition-all hover:underline"
                                        >
                                            #{{ $item->cod_settlement_id }}
                                        </a>
                                    </td>
                                    <td class="px-2 py-2 text-sm text-gray-700 dark:text-gray-300">
                                        @if ($item->codSettlement?->shipmentRecord)
                                            <a
                                                href="{{ route('admin.sales.shipment-operations.view', $item->codSettlement->shipment_record_id) }}"
                                                class="text-blue-600 transition-all hover:underline"
                                            >
                                                #{{ $item->codSettlement->shipment_record_id }}
                                            </a>
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td class="px-2 py-2 text-sm text-gray-700 dark:text-gray-300">
                                        {{ core()->formatBasePrice($item->expected_amount) }}
                                    </td>
                                    <td class="px-2 py-2 text-sm text-gray-700 dark:text-gray-300">
                                        {{ core()->formatBasePrice($item->remitted_amount) }}
                                    </td>
                                    <td class="px-2 py-2 text-sm text-gray-700 dark:text-gray-300">
                                        {{ core()->formatBasePrice($item->adjustment_amount) }}
                                    </td>
                                    <td class="px-2 py-2 text-sm text-gray-700 dark:text-gray-300">
                                        {{ core()->formatBasePrice($item->short_amount) }}
                                    </td>
                                    <td class="px-2 py-2 text-sm text-gray-700 dark:text-gray-300">
                                        {{ $item->codSettlement?->status_label ?? 'N/A' }}
                                    </td>
                                    <td class="px-2 py-2 text-sm text-gray-700 dark:text-gray-300">
                                        {{ $item->note ?: ($item->codSettlement?->dispute_note ?: 'N/A') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="flex w-[360px] max-w-full flex-col gap-2 max-xl:w-full">
            <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                    Reconciliation Health
                </p>

                <div class="grid gap-3 text-sm text-gray-600 dark:text-gray-300">
                    <div class="flex items-center justify-between gap-4">
                        <span>Settlements</span>
                        <span class="font-semibold text-gray-800 dark:text-white">{{ $summary['settlements_count'] }}</span>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <span>Settled Items</span>
                        <span class="font-semibold text-gray-800 dark:text-white">{{ $summary['settled_count'] }}</span>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <span>Short Items</span>
                        <span class="font-semibold text-gray-800 dark:text-white">{{ $summary['short_count'] }}</span>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <span>Disputed Items</span>
                        <span class="font-semibold text-gray-800 dark:text-white">{{ $summary['disputed_count'] }}</span>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <span>Written Off</span>
                        <span class="font-semibold text-gray-800 dark:text-white">{{ $summary['written_off_count'] }}</span>
                    </div>

                    <div class="flex items-center justify-between gap-4 border-t pt-3 dark:border-gray-800">
                        <span>Gap</span>
                        <span class="font-semibold text-gray-800 dark:text-white">{{ core()->formatBasePrice($summary['reconciliation_gap_amount']) }}</span>
                    </div>
                </div>
            </div>

            <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                    Totals
                </p>

                <div class="grid gap-3 text-sm text-gray-600 dark:text-gray-300">
                    <div class="flex items-center justify-between gap-4">
                        <span>Gross Expected</span>
                        <span class="font-semibold text-gray-800 dark:text-white">{{ core()->formatBasePrice($settlementBatch->gross_expected_amount) }}</span>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <span>Gross Remitted</span>
                        <span class="font-semibold text-gray-800 dark:text-white">{{ core()->formatBasePrice($settlementBatch->gross_remitted_amount) }}</span>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <span>Total Adjustments</span>
                        <span class="font-semibold text-gray-800 dark:text-white">{{ core()->formatBasePrice($settlementBatch->total_adjustment_amount) }}</span>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <span>Total Short</span>
                        <span class="font-semibold text-gray-800 dark:text-white">{{ core()->formatBasePrice($settlementBatch->total_short_amount) }}</span>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <span>Total Deductions</span>
                        <span class="font-semibold text-gray-800 dark:text-white">{{ core()->formatBasePrice($settlementBatch->total_deductions_amount) }}</span>
                    </div>

                    <div class="flex items-center justify-between gap-4 border-t pt-3 dark:border-gray-800">
                        <span>Net Amount</span>
                        <span class="font-semibold text-gray-800 dark:text-white">{{ core()->formatBasePrice($settlementBatch->net_amount) }}</span>
                    </div>
                </div>
            </div>

            @if ($settlementBatch->notes)
                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        Notes
                    </p>

                    <p class="whitespace-pre-line text-sm text-gray-600 dark:text-gray-300">
                        {{ $settlementBatch->notes }}
                    </p>
                </div>
            @endif
        </div>
    </div>
</x-admin::layouts>
