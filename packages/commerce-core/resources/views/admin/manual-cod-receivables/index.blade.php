<x-admin::layouts>
    <x-slot:title>
        COD Receivables
    </x-slot>

    <div class="grid gap-4">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="grid gap-1">
                    <p class="text-xl font-bold text-gray-900 dark:text-white">
                        COD Receivables
                    </p>

                    <p class="max-w-3xl text-sm text-gray-600 dark:text-gray-300">
                        This page shows how much each courier still owes your business for delivered COD orders. In this workflow, "collected by courier" means the customer paid the courier, while "received by merchant" means the courier has already remitted that money to your business.
                    </p>
                </div>

                <div class="rounded-lg bg-slate-50 px-4 py-3 text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                    <p class="font-semibold">Manual Basic mode</p>
                    <p>Courier totals stay simple here, while shipment-level records remain accurate underneath.</p>
                </div>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-sm font-semibold text-gray-900 dark:text-white">Receivable Amount Total</p>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    COD already collected by the courier from customers.
                </p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-sm font-semibold text-gray-900 dark:text-white">Received Amount Total</p>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    Money your business has already received from the courier.
                </p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-sm font-semibold text-gray-900 dark:text-white">Pending Amount Total</p>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    Money still pending from the courier to your business.
                </p>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            @if ($courierSummaries->isEmpty())
                <div class="p-10 text-center text-sm text-gray-600 dark:text-gray-300">
                    No COD receivables are ready yet. Delivered COD shipments will appear here after they move into collected-by-courier state.
                </div>
            @else
                <div class="overflow-x-auto">
                    <x-admin::table class="min-w-[980px]">
                        <thead class="bg-slate-50 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                            <tr>
                                <x-admin::table.th>Courier</x-admin::table.th>
                                <x-admin::table.th>Receivable Amount Total</x-admin::table.th>
                                <x-admin::table.th>Received Amount Total</x-admin::table.th>
                                <x-admin::table.th>Pending Amount Total</x-admin::table.th>
                                <x-admin::table.th>Action</x-admin::table.th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($courierSummaries as $summary)
                                @php
                                    $isRestoringModal = (string) old('shipment_carrier_id') === (string) $summary['carrier_id'];
                                @endphp

                                <tr class="border-t border-slate-200 align-top dark:border-gray-800">
                                    <x-admin::table.td class="whitespace-normal">
                                        <div class="grid gap-1">
                                            <span class="font-semibold text-gray-800 dark:text-gray-100">
                                                {{ $summary['courier_name'] }}
                                            </span>

                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                Based on {{ $summary['settlement_count'] }} delivered COD shipment(s)
                                            </span>
                                        </div>
                                    </x-admin::table.td>

                                    <x-admin::table.td>
                                        {{ $summary['receivable_total_formatted'] }}
                                    </x-admin::table.td>

                                    <x-admin::table.td>
                                        {{ $summary['received_total_formatted'] }}
                                    </x-admin::table.td>

                                    <x-admin::table.td>
                                        <span class="font-semibold text-gray-900 dark:text-white">
                                            {{ $summary['pending_total_formatted'] }}
                                        </span>
                                    </x-admin::table.td>

                                    <x-admin::table.td class="whitespace-normal">
                                        @if ($summary['can_record_receipt'])
                                            <x-admin::modal :is-active="$isRestoringModal">
                                                <x-slot:toggle>
                                                    <button
                                                        type="button"
                                                        class="inline-flex rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700"
                                                    >
                                                        Record COD Received
                                                    </button>
                                                </x-slot>

                                                <x-slot:header>
                                                    <div class="grid gap-1">
                                                        <p class="text-lg font-bold text-gray-800 dark:text-white">
                                                            Record COD Received
                                                        </p>

                                                        <p class="text-sm font-normal text-gray-600 dark:text-gray-300">
                                                            {{ $summary['courier_name'] }} still has {{ $summary['pending_total_formatted'] }} pending. The amount you enter is treated as money received by the merchant and will be applied to the oldest pending deliveries first.
                                                        </p>
                                                    </div>
                                                </x-slot>

                                                <x-slot:content>
                                                    <x-admin::form
                                                        method="POST"
                                                        :action="route('admin.sales.cod-receivables.record-received')"
                                                    >
                                                        <input type="hidden" name="shipment_carrier_id" value="{{ $summary['carrier_id'] }}">

                                                        <div class="grid gap-4">
                                                            <div class="rounded-lg bg-slate-50 px-4 py-3 text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                                                <p class="font-semibold">Pending amount</p>
                                                                <p>{{ $summary['pending_total_formatted'] }}</p>
                                                            </div>

                                                            <x-admin::form.control-group class="!mb-0">
                                                                <x-admin::form.control-group.label class="required">
                                                                    Amount Received
                                                                </x-admin::form.control-group.label>

                                                                <x-admin::form.control-group.control
                                                                    type="text"
                                                                    name="amount"
                                                                    :value="$isRestoringModal ? old('amount') : ''"
                                                                    rules="required"
                                                                    :label="'Amount Received'"
                                                                    :placeholder="'Enter received amount'"
                                                                />

                                                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                                                    Enter the amount your business just received from this courier.
                                                                </p>

                                                                <x-admin::form.control-group.error control-name="amount" />
                                                            </x-admin::form.control-group>

                                                            <x-admin::form.control-group class="!mb-0">
                                                                <x-admin::form.control-group.label>
                                                                    Note
                                                                </x-admin::form.control-group.label>

                                                                <x-admin::form.control-group.control
                                                                    type="textarea"
                                                                    name="note"
                                                                    :value="$isRestoringModal ? old('note') : ''"
                                                                    :label="'Note'"
                                                                    :placeholder="'Optional note for this receipt'"
                                                                />

                                                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                                                    Optional. Use this if you want to remember transfer details or handover notes.
                                                                </p>

                                                                <x-admin::form.control-group.error control-name="note" />
                                                            </x-admin::form.control-group>
                                                        </div>

                                                        <div class="mt-6 flex items-center justify-end gap-3">
                                                            <button
                                                                type="submit"
                                                                class="inline-flex rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700"
                                                            >
                                                                Save Receipt
                                                            </button>
                                                        </div>
                                                    </x-admin::form>
                                                </x-slot>
                                            </x-admin::modal>
                                        @else
                                            <span class="text-xs font-semibold uppercase tracking-wide text-emerald-600">
                                                Up to date
                                            </span>
                                        @endif
                                    </x-admin::table.td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-admin::table>
                </div>
            @endif
        </div>
    </div>
</x-admin::layouts>
