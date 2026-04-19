<x-admin::layouts>
    <x-slot:title>
        COD Settlement #{{ $codSettlement->id }}
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            COD Settlement #{{ $codSettlement->id }}
        </p>

        <div class="flex items-center gap-x-2.5">
            <a
                href="{{ route('admin.sales.cod-settlements.index') }}"
                class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
            >
                Back
            </a>

            @if ($codSettlement->shipmentRecord)
                <a
                    href="{{ route('admin.sales.shipment-operations.view', $codSettlement->shipment_record_id) }}"
                    class="primary-button"
                >
                    View Shipment Ops
                </a>
            @endif
        </div>
    </div>

    <div class="mt-3.5 flex gap-2.5 max-xl:flex-wrap">
        <div class="flex flex-1 flex-col gap-2 max-xl:flex-auto">
            <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                <div class="flex items-start justify-between gap-4 max-md:flex-wrap">
                    <div>
                        <p class="text-base font-semibold text-gray-800 dark:text-white">
                            Settlement Summary
                        </p>

                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                            {{ $codSettlement->status_label }}
                        </p>
                    </div>

                    @if (bouncer()->hasPermission('sales.cod_settlements.update'))
                        <form
                            method="POST"
                            action="{{ route('admin.sales.cod-settlements.update', $codSettlement) }}"
                            class="grid gap-2 md:min-w-[360px]"
                        >
                            @csrf

                            <select
                                name="status"
                                class="custom-select w-full rounded-md border bg-white px-3 py-2.5 text-sm text-gray-600 transition-all hover:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400"
                            >
                                @foreach ($statusOptions as $status => $label)
                                    <option
                                        value="{{ $status }}"
                                        @selected(old('status', $codSettlement->status) === $status)
                                    >
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>

                            <div class="grid grid-cols-2 gap-2">
                                <input
                                    type="number"
                                    step="0.01"
                                    name="collected_amount"
                                    value="{{ old('collected_amount', $codSettlement->collected_amount) }}"
                                    class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                                    placeholder="Collected amount"
                                />

                                <input
                                    type="number"
                                    step="0.01"
                                    name="remitted_amount"
                                    value="{{ old('remitted_amount', $codSettlement->remitted_amount) }}"
                                    class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                                    placeholder="Remitted amount"
                                />

                                <input
                                    type="number"
                                    step="0.01"
                                    name="short_amount"
                                    value="{{ old('short_amount', $codSettlement->short_amount) }}"
                                    class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                                    placeholder="Short amount"
                                />

                                <input
                                    type="number"
                                    step="0.01"
                                    name="disputed_amount"
                                    value="{{ old('disputed_amount', $codSettlement->disputed_amount) }}"
                                    class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                                    placeholder="Disputed amount"
                                />

                                <input
                                    type="number"
                                    step="0.01"
                                    name="carrier_fee_amount"
                                    value="{{ old('carrier_fee_amount', $codSettlement->carrier_fee_amount) }}"
                                    class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                                    placeholder="Carrier fee"
                                />

                                <input
                                    type="number"
                                    step="0.01"
                                    name="cod_fee_amount"
                                    value="{{ old('cod_fee_amount', $codSettlement->cod_fee_amount) }}"
                                    class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                                    placeholder="COD fee"
                                />

                                <input
                                    type="number"
                                    step="0.01"
                                    name="return_fee_amount"
                                    value="{{ old('return_fee_amount', $codSettlement->return_fee_amount) }}"
                                    class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 col-span-2"
                                    placeholder="Return fee"
                                />
                            </div>

                            <textarea
                                name="dispute_note"
                                rows="2"
                                class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                                placeholder="Dispute note"
                            >{{ old('dispute_note', $codSettlement->dispute_note) }}</textarea>

                            <textarea
                                name="notes"
                                rows="2"
                                class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                                placeholder="Notes"
                            >{{ old('notes', $codSettlement->notes) }}</textarea>

                            <button
                                type="submit"
                                class="primary-button"
                            >
                                Update COD Settlement
                            </button>
                        </form>
                    @endif
                </div>

                @if ($summary['requires_attention'])
                    <div class="mt-4 rounded border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100">
                        <p class="font-semibold">
                            Attention Required
                        </p>

                        <p class="mt-1">
                            {{ $summary['health_label'] }}.
                            Outstanding amount: {{ core()->formatBasePrice($summary['outstanding_amount']) }}.
                        </p>
                    </div>
                @endif

                <div class="mt-4 grid grid-cols-2 gap-4 max-md:grid-cols-1">
                    <div>
                        <p class="font-semibold text-gray-800 dark:text-white">
                            Order
                        </p>

                        <p class="text-gray-600 dark:text-gray-300">
                            {{ $codSettlement->order?->increment_id ?? 'N/A' }}
                        </p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-800 dark:text-white">
                            Shipment Ops
                        </p>

                        <p class="text-gray-600 dark:text-gray-300">
                            #{{ $codSettlement->shipment_record_id }}
                        </p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-800 dark:text-white">
                            Carrier
                        </p>

                        <p class="text-gray-600 dark:text-gray-300">
                            {{ $codSettlement->carrier?->name ?? $codSettlement->shipmentRecord?->carrier_name_snapshot ?? 'N/A' }}
                        </p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-800 dark:text-white">
                            Shipment Status
                        </p>

                        <p class="text-gray-600 dark:text-gray-300">
                            {{ $codSettlement->shipmentRecord?->status_label ?? 'N/A' }}
                        </p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-800 dark:text-white">
                            Delivered
                        </p>

                        <p class="text-gray-600 dark:text-gray-300">
                            {{ $codSettlement->shipmentRecord?->delivered_at?->format('d M, Y h:i a') ?? 'N/A' }}
                        </p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-800 dark:text-white">
                            Remitted
                        </p>

                        <p class="text-gray-600 dark:text-gray-300">
                            {{ $codSettlement->remitted_at?->format('d M, Y h:i a') ?? 'N/A' }}
                        </p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-800 dark:text-white">
                            Settled
                        </p>

                        <p class="text-gray-600 dark:text-gray-300">
                            {{ $codSettlement->settled_at?->format('d M, Y h:i a') ?? 'N/A' }}
                        </p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-800 dark:text-white">
                            Reconciliation Health
                        </p>

                        <p class="text-gray-600 dark:text-gray-300">
                            {{ $summary['health_label'] }}
                        </p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-800 dark:text-white">
                            Outstanding
                        </p>

                        <p class="text-gray-600 dark:text-gray-300">
                            {{ core()->formatBasePrice($summary['outstanding_amount']) }}
                        </p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-800 dark:text-white">
                            Settlement Batch
                        </p>

                        <p class="text-gray-600 dark:text-gray-300">
                            @if ($summary['batch_id'])
                                <a
                                    href="{{ route('admin.sales.settlement-batches.view', $summary['batch_id']) }}"
                                    class="text-blue-600 transition-all hover:underline"
                                >
                                    {{ $summary['batch_reference'] }}
                                </a>
                            @else
                                Not batched yet
                            @endif
                        </p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-800 dark:text-white">
                            Last Updated By
                        </p>

                        <p class="text-gray-600 dark:text-gray-300">
                            {{ $codSettlement->updater?->name ?? $codSettlement->creator?->name ?? 'N/A' }}
                        </p>
                    </div>
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
                        <span>Health</span>
                        <span class="font-semibold text-gray-800 dark:text-white">{{ $summary['health_label'] }}</span>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <span>Outstanding</span>
                        <span class="font-semibold text-gray-800 dark:text-white">{{ core()->formatBasePrice($summary['outstanding_amount']) }}</span>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <span>Needs Attention</span>
                        <span class="font-semibold text-gray-800 dark:text-white">{{ $summary['requires_attention'] ? 'Yes' : 'No' }}</span>
                    </div>
                </div>
            </div>

            <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                    Financial Snapshot
                </p>

                <div class="grid gap-3 text-sm text-gray-600 dark:text-gray-300">
                    <div class="flex items-center justify-between gap-4">
                        <span>Expected</span>
                        <span class="font-semibold text-gray-800 dark:text-white">{{ core()->formatBasePrice($codSettlement->expected_amount) }}</span>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <span>Collected</span>
                        <span class="font-semibold text-gray-800 dark:text-white">{{ core()->formatBasePrice($codSettlement->collected_amount) }}</span>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <span>Remitted</span>
                        <span class="font-semibold text-gray-800 dark:text-white">{{ core()->formatBasePrice($codSettlement->remitted_amount) }}</span>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <span>Short</span>
                        <span class="font-semibold text-gray-800 dark:text-white">{{ core()->formatBasePrice($codSettlement->short_amount) }}</span>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <span>Disputed</span>
                        <span class="font-semibold text-gray-800 dark:text-white">{{ core()->formatBasePrice($codSettlement->disputed_amount) }}</span>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <span>Carrier Fee</span>
                        <span class="font-semibold text-gray-800 dark:text-white">{{ core()->formatBasePrice($codSettlement->carrier_fee_amount) }}</span>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <span>COD Fee</span>
                        <span class="font-semibold text-gray-800 dark:text-white">{{ core()->formatBasePrice($codSettlement->cod_fee_amount) }}</span>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <span>Return Fee</span>
                        <span class="font-semibold text-gray-800 dark:text-white">{{ core()->formatBasePrice($codSettlement->return_fee_amount) }}</span>
                    </div>

                    <div class="flex items-center justify-between gap-4 border-t pt-3 dark:border-gray-800">
                        <span>Net</span>
                        <span class="font-semibold text-gray-800 dark:text-white">{{ core()->formatBasePrice($codSettlement->net_amount) }}</span>
                    </div>
                </div>
            </div>

            @if ($codSettlement->dispute_note || $codSettlement->notes)
                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        Notes
                    </p>

                    <div class="grid gap-3 text-sm text-gray-600 dark:text-gray-300">
                        @if ($codSettlement->dispute_note)
                            <div>
                                <p class="font-semibold text-gray-800 dark:text-white">
                                    Dispute Note
                                </p>

                                <p class="whitespace-pre-line">{{ $codSettlement->dispute_note }}</p>
                            </div>
                        @endif

                        @if ($codSettlement->notes)
                            <div>
                                <p class="font-semibold text-gray-800 dark:text-white">
                                    Notes
                                </p>

                                <p class="whitespace-pre-line">{{ $codSettlement->notes }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-admin::layouts>
