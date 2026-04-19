<x-admin::layouts>
    <x-slot:title>
        Create Settlement Batch
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            Create Settlement Batch
        </p>

        <a
            href="{{ route('admin.sales.settlement-batches.index') }}"
            class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
        >
            Back
        </a>
    </div>

    <form
        method="POST"
        action="{{ route('admin.sales.settlement-batches.store') }}"
        class="mt-4 grid gap-4"
    >
        @csrf

        <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
            <div class="grid grid-cols-2 gap-4 max-lg:grid-cols-1">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800 dark:text-white">
                        Reference
                    </label>

                    <input
                        type="text"
                        name="reference"
                        value="{{ old('reference') }}"
                        class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                        placeholder="BATCH-20260418-01"
                    />
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800 dark:text-white">
                        Carrier
                    </label>

                    <select
                        name="shipment_carrier_id"
                        class="custom-select w-full rounded-md border bg-white px-3 py-2.5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                    >
                        <option value="">
                            Auto from selected settlements
                        </option>

                        @foreach ($carriers as $carrier)
                            <option
                                value="{{ $carrier->id }}"
                                @selected((string) old('shipment_carrier_id') === (string) $carrier->id)
                            >
                                {{ $carrier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800 dark:text-white">
                        Payout Method
                    </label>

                    <input
                        type="text"
                        name="payout_method"
                        value="{{ old('payout_method') }}"
                        class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                        placeholder="bkash"
                    />
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-gray-800 dark:text-white">
                        Batch Status
                    </label>

                    <select
                        name="status"
                        class="custom-select w-full rounded-md border bg-white px-3 py-2.5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                    >
                        @foreach ($statusOptions as $status => $label)
                            <option
                                value="{{ $status }}"
                                @selected(old('status', \Platform\CommerceCore\Models\SettlementBatch::STATUS_DRAFT) === $status)
                            >
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-4">
                <label class="mb-1.5 block text-sm font-semibold text-gray-800 dark:text-white">
                    Notes
                </label>

                <textarea
                    name="notes"
                    rows="3"
                    class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                    placeholder="Optional payout or reconciliation notes"
                >{{ old('notes') }}</textarea>
            </div>
        </div>

        <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
            <div class="flex items-center justify-between gap-4 max-md:flex-wrap">
                <p class="text-base font-semibold text-gray-800 dark:text-white">
                    Select COD Settlements
                </p>

                <p class="text-sm text-gray-600 dark:text-gray-300">
                    Expected payout uses each settlement net amount by default.
                </p>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full min-w-[960px] table-auto border-separate border-spacing-y-2">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-gray-500">
                            <th class="px-2 py-1">Select</th>
                            <th class="px-2 py-1">Order</th>
                            <th class="px-2 py-1">Shipment</th>
                            <th class="px-2 py-1">Carrier</th>
                            <th class="px-2 py-1">Status</th>
                            <th class="px-2 py-1">Expected Net</th>
                            <th class="px-2 py-1">Remitted</th>
                            <th class="px-2 py-1">Adjustment</th>
                            <th class="px-2 py-1">Item Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($eligibleSettlements as $settlement)
                            <tr class="rounded border border-gray-200 align-top dark:border-gray-800">
                                <td class="px-2 py-2">
                                    <input
                                        type="checkbox"
                                        name="settlement_ids[]"
                                        value="{{ $settlement->id }}"
                                        @checked(collect(old('settlement_ids', []))->contains($settlement->id))
                                    />
                                </td>
                                <td class="px-2 py-2 text-sm text-gray-700 dark:text-gray-300">
                                    {{ $settlement->order?->increment_id ?? 'N/A' }}
                                </td>
                                <td class="px-2 py-2 text-sm text-gray-700 dark:text-gray-300">
                                    #{{ $settlement->shipment_record_id }}
                                </td>
                                <td class="px-2 py-2 text-sm text-gray-700 dark:text-gray-300">
                                    {{ $settlement->carrier?->name ?? $settlement->shipmentRecord?->carrier_name_snapshot ?? 'N/A' }}
                                </td>
                                <td class="px-2 py-2 text-sm text-gray-700 dark:text-gray-300">
                                    {{ $settlement->status_label }}
                                </td>
                                <td class="px-2 py-2 text-sm text-gray-700 dark:text-gray-300">
                                    {{ core()->formatBasePrice($settlement->net_amount) }}
                                </td>
                                <td class="px-2 py-2">
                                    <input
                                        type="number"
                                        step="0.01"
                                        name="remitted_amounts[{{ $settlement->id }}]"
                                        value="{{ old('remitted_amounts.'.$settlement->id, $settlement->net_amount) }}"
                                        class="w-32 rounded-md border px-3 py-2 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                                    />
                                </td>
                                <td class="px-2 py-2">
                                    <input
                                        type="number"
                                        step="0.01"
                                        name="adjustment_amounts[{{ $settlement->id }}]"
                                        value="{{ old('adjustment_amounts.'.$settlement->id, 0) }}"
                                        class="w-28 rounded-md border px-3 py-2 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                                    />
                                </td>
                                <td class="px-2 py-2">
                                    <input
                                        type="text"
                                        name="item_notes[{{ $settlement->id }}]"
                                        value="{{ old('item_notes.'.$settlement->id) }}"
                                        class="w-full rounded-md border px-3 py-2 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                                        placeholder="Optional item note"
                                    />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-2 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    No eligible COD settlements available for batching.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="primary-button">
                Create Settlement Batch
            </button>
        </div>
    </form>
</x-admin::layouts>
