<x-admin::layouts>
    <x-slot:title>
        Transactions
    </x-slot>

    <div class="space-y-8 bg-transparent pb-8" style="background-color: #eff3f8;">
        <section class="flex flex-col gap-4 pt-1 sm:flex-row sm:items-center sm:justify-between">
            <h1 class="text-3xl font-semibold tracking-tight text-slate-950 md:text-4xl dark:text-white">
                Transactions
            </h1>

            <div class="flex flex-wrap items-center gap-2.5 max-sm:w-full">
                <x-admin::datagrid.export :src="route('admin.sales.transactions.index')" />

                <v-create-transaction-form>
                    <button
                        type="button"
                        class="primary-button !rounded-xl !px-4 !py-2 !text-sm !shadow-sm !shadow-blue-200/60"
                    >
                        Create Invoice Payment
                    </button>
                </v-create-transaction-form>
            </div>
        </section>

        <v-transaction-drawer ref="transactionDrawer" />
    </div>

    @pushOnce('styles')
        <style>
            .sales-transactions-modern-datagrid > .mt-7 {
                margin-top: 0;
            }

            .sales-transactions-modern-datagrid > .mt-4 {
                margin-top: 1rem;
            }

            .sales-transactions-modern-datagrid .table-responsive.box-shadow {
                border: 0;
                border-radius: 1.25rem;
                box-shadow: 0 1px 2px 0 rgb(148 163 184 / 0.18);
                background: #ffffff;
                overflow: hidden;
            }

            .dark .sales-transactions-modern-datagrid .table-responsive.box-shadow {
                background: rgb(15 23 42);
            }

            .sales-transactions-modern-datagrid .table-responsive > .row {
                border-color: rgb(226 232 240);
            }

            .sales-transactions-modern-datagrid .table-responsive > .row:first-child {
                background: rgb(248 250 252 / 0.8);
                color: rgb(100 116 139);
                font-size: 0.75rem;
                letter-spacing: 0.025em;
                text-transform: uppercase;
            }

            .dark .sales-transactions-modern-datagrid .table-responsive > .row {
                border-color: rgb(30 41 59);
            }

            .dark .sales-transactions-modern-datagrid .table-responsive > .row:first-child {
                background: rgb(2 6 23 / 0.4);
                color: rgb(148 163 184);
            }
        </style>
    @endPushOnce

    @pushOnce('scripts')
        <script type="text/x-template" id="v-transaction-drawer-template">
            <x-admin::datagrid
                class="sales-transactions-modern-datagrid"
                :src="route('admin.sales.transactions.index')"
                :isMultiRow="true"
                ref="datagrid"
            >
                <template #body="{
                    isLoading,
                    available
                }">
                    <template v-if="isLoading">
                        <x-admin::shimmer.datagrid.table.body />
                    </template>

                    <template v-else>
                        <div
                            v-for="record in available.records"
                            class="row grid items-center gap-3 border-b border-slate-100 px-4 py-4 text-sm text-slate-600 transition hover:bg-slate-50/80 dark:border-slate-800 dark:text-slate-300 dark:hover:bg-slate-800/60"
                            :style="`grid-template-columns: 1.1fr .9fr .9fr 1.5fr 1.1fr .8fr .8fr .3fr`"
                        >
                            <p class="break-words font-semibold text-slate-950 dark:text-white" v-text="record.transaction_ref"></p>

                            <p class="break-words" v-text="record.transaction_date"></p>

                            <p class="break-words" v-html="record.type_label"></p>

                            <p class="break-words" v-text="record.source"></p>

                            <p class="break-words" v-text="record.counterparty"></p>

                            <p class="break-words font-semibold text-slate-950 dark:text-white" v-text="record.amount"></p>

                            <p class="break-words" v-text="record.status"></p>

                            @if (bouncer()->hasPermission('sales.transactions.view'))
                                <div class="flex justify-end">
                                    <a
                                        v-if="record.actions.find(action => action.title === '@lang('admin::app.sales.transactions.index.datagrid.view')')"
                                        @click="view(record.actions.find(action => action.title === '@lang('admin::app.sales.transactions.index.datagrid.view')')?.url)"
                                    >
                                        <span
                                            class="icon-sort-right rtl:icon-sort-left cursor-pointer rounded-lg p-1.5 text-2xl transition hover:bg-slate-100 dark:hover:bg-slate-800 ltr:ml-1 rtl:mr-1"
                                            role="button"
                                            tabindex="0"
                                        ></span>
                                    </a>
                                </div>
                            @endif
                        </div>
                    </template>
                </template>
            </x-admin::datagrid>

            <div class="flex flex-1 flex-col gap-2 max-xl:flex-auto">
                <x-admin::drawer ref="transaction">
                    <x-slot:header>
                        <div class="grid gap-y-1 py-3 dark:border-gray-800 max-sm:px-4">
                            <p class="text-xl font-medium dark:text-white">
                                Transaction Detail
                            </p>

                            <p class="text-sm text-gray-500 dark:text-gray-400" v-text="data.type_label"></p>
                        </div>
                    </x-slot>

                    <x-slot:content>
                        <div class="grid gap-5 text-sm">
                            <div class="rounded-lg border border-slate-200 p-4 dark:border-gray-800">
                                <div class="grid gap-3 md:grid-cols-2">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Reference</p>
                                        <p class="mt-1 font-semibold text-gray-800 dark:text-white" v-text="data.transaction_ref"></p>
                                    </div>

                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Date</p>
                                        <p class="mt-1 text-gray-700 dark:text-gray-300" v-text="data.created_at"></p>
                                    </div>

                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Source</p>
                                        <p class="mt-1 text-gray-700 dark:text-gray-300" v-text="data.source"></p>
                                    </div>

                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Counterparty</p>
                                        <p class="mt-1 text-gray-700 dark:text-gray-300" v-text="data.counterparty"></p>
                                    </div>

                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Amount</p>
                                        <p class="mt-1 font-semibold text-gray-800 dark:text-white" v-text="data.amount"></p>
                                    </div>

                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Status</p>
                                        <p class="mt-1 text-gray-700 dark:text-gray-300" v-text="data.status"></p>
                                    </div>

                                    <div v-if="data.payment_method">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Method</p>
                                        <p class="mt-1 text-gray-700 dark:text-gray-300" v-text="data.payment_method"></p>
                                    </div>

                                    <div v-if="data.allocated_amount">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Allocated</p>
                                        <p class="mt-1 text-gray-700 dark:text-gray-300">
                                            @{{ data.allocated_amount }} allocated · @{{ data.unallocated_amount }} unallocated
                                        </p>
                                    </div>
                                </div>

                                <p
                                    v-if="data.note"
                                    class="mt-4 rounded-lg bg-slate-50 p-3 text-gray-600 dark:bg-gray-800 dark:text-gray-300"
                                    v-text="data.note"
                                ></p>
                            </div>

                            <div
                                v-if="data.allocations && data.allocations.length"
                                class="rounded-lg border border-slate-200 p-4 dark:border-gray-800"
                            >
                                <p class="font-semibold text-gray-800 dark:text-white">
                                    COD Allocation Detail
                                </p>

                                <div class="mt-4 overflow-x-auto">
                                    <table class="w-full min-w-[680px] text-left">
                                        <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-gray-500 dark:border-gray-800">
                                            <tr>
                                                <th class="py-2 pr-3">Order</th>
                                                <th class="py-2 pr-3">Settlement</th>
                                                <th class="py-2 pr-3">Shipment</th>
                                                <th class="py-2 pr-3">Allocated Amount</th>
                                                <th class="py-2 pr-3">Status</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <tr
                                                v-for="allocation in data.allocations"
                                                class="border-b border-slate-100 last:border-b-0 dark:border-gray-800"
                                            >
                                                <td class="py-2 pr-3" v-text="allocation.order"></td>
                                                <td class="py-2 pr-3" v-text="allocation.settlement"></td>
                                                <td class="py-2 pr-3" v-text="allocation.shipment"></td>
                                                <td class="py-2 pr-3 font-semibold text-gray-800 dark:text-white" v-text="allocation.amount"></td>
                                                <td class="py-2 pr-3" v-text="allocation.status"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </x-slot>
                </x-admin::drawer>
            </div>
        </script>

        <script type="text/x-template" id="v-create-transaction-form-template">
            <div>
                <button
                    type="button"
                    class="primary-button !rounded-xl !px-4 !py-2 !text-sm !shadow-sm !shadow-blue-200/60"
                    @click="$refs.transactionModel.toggle()"
                >
                    Create Invoice Payment
                </button>

                <x-admin::form v-slot="{ meta, errors, handleSubmit }" as="div">
                    <form @submit="handleSubmit($event, store)">
                        <x-admin::modal ref="transactionModel">
                            <x-slot:header>
                                <p class="text-lg font-bold text-gray-800 dark:text-white">
                                    Create Invoice Payment
                                </p>
                            </x-slot>

                            <x-slot:content>
                                <div class="mb-4 rounded-lg border border-blue-100 bg-blue-50 p-3 text-sm text-blue-800">
                                    Use this only for invoice-based prepaid or manual payments. Courier COD receipts are recorded from COD Receivables.
                                </div>

                                <x-admin::form.control-group class="mb-2.5 w-full">
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.sales.transactions.index.create.invoice-id')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        id="invoice_id"
                                        name="invoice_id"
                                        rules="required"
                                        :label="trans('admin::app.sales.transactions.index.create.invoice-id')"
                                        :placeholder="trans('admin::app.sales.transactions.index.create.invoice-id')"
                                    />

                                    <x-admin::form.control-group.error control-name="invoice_id" />
                                </x-admin::form.control-group>

                                <x-admin::form.control-group class="mb-2.5 w-full">
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.sales.transactions.index.create.payment-method')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="select"
                                        id="payment_method"
                                        name="payment_method"
                                        rules="required"
                                        :label="trans('admin::app.sales.transactions.index.create.payment-method')"
                                        :placeholder="trans('admin::app.sales.transactions.index.create.payment-method')"
                                    >
                                        <option
                                            v-for="paymentMethod in paymentMethods"
                                            :value="paymentMethod.method"
                                            v-text="paymentMethod.method_title"
                                        ></option>
                                    </x-admin::form.control-group.control>

                                    <x-admin::form.control-group.error control-name="payment_method" />
                                </x-admin::form.control-group>

                                <x-admin::form.control-group class="mb-2.5 w-full">
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.sales.transactions.index.create.amount')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="text"
                                        id="amount"
                                        name="amount"
                                        rules="required"
                                        :label="trans('admin::app.sales.transactions.index.create.amount')"
                                        :placeholder="trans('admin::app.sales.transactions.index.create.amount')"
                                    />

                                    <x-admin::form.control-group.error control-name="amount" />
                                </x-admin::form.control-group>
                            </x-slot>

                            <x-slot:footer>
                                <x-admin::button
                                    button-type="submit"
                                    class="primary-button"
                                    :title="trans('admin::app.sales.transactions.index.create.save-transaction')"
                                    ::loading="isLoading"
                                    ::disabled="isLoading"
                                />
                            </x-slot>
                        </x-admin::modal>
                    </form>
                </x-admin::form>
            </div>
        </script>

        <script type="module">
            app.component('v-transaction-drawer', {
                template: '#v-transaction-drawer-template',

                data() {
                    return {
                        data: {},
                    }
                },

                methods: {
                    view(url) {
                        this.$axios.get(url)
                            .then((response) => {
                                this.$refs.transaction.open();

                                this.data = response.data.data;
                            })
                            .catch(error => {
                                this.$emitter.emit('add-flash', {
                                    type: 'error',
                                    message: error.response?.data?.message || 'Unable to load transaction details.',
                                });
                            });
                    },
                }
            });
        </script>

        <script type="module">
            app.component('v-create-transaction-form', {
                template: '#v-create-transaction-form-template',

                data() {
                    return {
                        paymentMethods: @json(payment()->getSupportedPaymentMethods()['payment_methods']),
                        isLoading: false,
                    };
                },

                methods: {
                    store(params, { setErrors, resetForm }) {
                        this.isLoading = true;

                        this.$axios.post('{{ route('admin.sales.transactions.store') }}', params)
                            .then((response) => {
                                this.isLoading = false;

                                this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });

                                this.$refs.transactionModel.toggle();

                                this.$parent.$refs.transactionDrawer.$refs.datagrid.get();

                                resetForm();
                            })
                            .catch((error) => {
                                this.isLoading = false;

                                if (error.response.status == 422) {
                                    setErrors(error.response.data.errors);
                                } else {
                                    this.$emitter.emit('add-flash', { type: 'error', message: error.response.data.message });
                                }
                            });
                    },
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts>
