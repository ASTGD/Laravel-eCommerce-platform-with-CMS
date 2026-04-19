<x-admin::layouts>
    <x-slot:title>
        Import Settlement Batch CSV
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            Import Settlement Batch CSV
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
        action="{{ route('admin.sales.settlement-batches.import-store') }}"
        enctype="multipart/form-data"
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
                        placeholder="BATCH-20260419-01"
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
                            Auto match from CSV rows
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
                        placeholder="bank_transfer"
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
                                @selected(old('status', \Platform\CommerceCore\Models\SettlementBatch::STATUS_RECONCILED) === $status)
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
                    placeholder="Optional courier remittance note"
                >{{ old('notes') }}</textarea>
            </div>
        </div>

        <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
            <p class="text-sm font-semibold text-gray-800 dark:text-white">
                CSV Requirements
            </p>

            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                Required columns: <code>remitted_amount</code> and one identifier column:
                <code>tracking_number</code> or <code>order_increment_id</code>.
            </p>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                Optional columns: <code>adjustment_amount</code>, <code>item_note</code>, <code>note</code>.
            </p>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                Example header: <code>tracking_number,remitted_amount,adjustment_amount,item_note</code>
            </p>

            <div class="mt-4">
                <label class="mb-1.5 block text-sm font-semibold text-gray-800 dark:text-white">
                    CSV File
                </label>

                <input
                    type="file"
                    name="import_file"
                    accept=".csv,.txt,text/csv"
                    class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                />
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="primary-button">
                Import Settlement CSV
            </button>
        </div>
    </form>
</x-admin::layouts>
