<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.settings.exchange-rates.index.title')
    </x-slot>

    {!! view_render_event('bagisto.admin.settings.exchange_rates.create.before') !!}

    <v-exchange-rates>
        <div class="space-y-8 bg-transparent pb-8" style="background-color: #f5f5f5;">
            <section class="flex flex-col gap-4 pt-1 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="text-3xl font-semibold tracking-tight text-slate-950 md:text-4xl dark:text-white">
                    @lang('admin::app.settings.exchange-rates.index.title')
                </h1>

                <div class="flex flex-wrap items-center gap-2.5 max-sm:w-full">
                    <!-- Update Exchange Rate Button -->
                    <a
                        href="{{ route('admin.settings.exchange_rates.update_rates') }}"
                        class="primary-button !rounded-xl !px-4 !py-2 !text-sm !shadow-sm !shadow-blue-200/60"
                    >
                        @lang('admin::app.settings.exchange-rates.index.update-rates')
                    </a>

                    <!-- Create Button -->
                    @if (bouncer()->hasPermission('settings.exchange_rates.create'))
                        <button
                            type="button"
                            class="primary-button !rounded-xl !px-4 !py-2 !text-sm !shadow-sm !shadow-blue-200/60"
                        >
                            @lang('admin::app.settings.exchange-rates.index.create-btn')
                        </button>
                    @endif
                </div>
            </section>

            <!-- DataGrid Shimmer -->
            <x-admin::shimmer.datagrid />
        </div>
    </v-exchange-rates>

    {!! view_render_event('bagisto.admin.settings.exchange_rates.create.after') !!}

    @include('admin::settings.partials.modern-index-styles')

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-exchange-rates-template"
        >
            <div class="space-y-8 bg-transparent pb-8" style="background-color: #f5f5f5;">
                <section class="flex flex-col gap-4 pt-1 sm:flex-row sm:items-center sm:justify-between">
                    <h1 class="text-3xl font-semibold tracking-tight text-slate-950 md:text-4xl dark:text-white">
                        @lang('admin::app.settings.exchange-rates.index.title')
                    </h1>

                    <div class="flex flex-wrap items-center gap-2.5 max-sm:w-full">
                        <!-- Update Exchange Rate Button -->
                        <a
                            href="{{ route('admin.settings.exchange_rates.update_rates') }}"
                            class="primary-button !rounded-xl !px-4 !py-2 !text-sm !shadow-sm !shadow-blue-200/60"
                        >
                            @lang('admin::app.settings.exchange-rates.index.update-rates')
                        </a>

                        <!-- Create Button -->
                        @if (bouncer()->hasPermission('settings.exchange_rates.create'))
                            <button
                                type="button"
                                class="primary-button !rounded-xl !px-4 !py-2 !text-sm !shadow-sm !shadow-blue-200/60"
                                @click="selectedExchangeRates=0;resetForm();$refs.exchangeRateUpdateOrCreateModal.toggle()"
                            >
                                @lang('admin::app.settings.exchange-rates.index.create-btn')
                            </button>
                        @endif
                    </div>
                </section>

                <x-admin::datagrid
                    class="settings-modern-datagrid"
                    :src="route('admin.settings.exchange_rates.index')"
                    ref="datagrid"
                >
                <template #body="{
                    isLoading,
                    available,
                    applied,
                    selectAll,
                    sort,
                    performAction
                }">
                    <template v-if="isLoading">
                        <x-admin::shimmer.datagrid.table.body />
                    </template>

                    <template v-else>
                        <div
                            v-for="record in available.records"
                            class="row grid items-center gap-2.5 border-b border-slate-100 px-4 py-4 text-gray-600 transition hover:bg-slate-50/80 dark:border-slate-800 dark:text-gray-300 dark:hover:bg-slate-800/60"
                            :style="`grid-template-columns: repeat(${gridsCount}, minmax(0, 1fr))`"
                        >
                            <!-- ID -->
                            <p>@{{ record.currency_exchange_id }}</p>

                            <!-- Status -->
                            <p>@{{ record.currency_name }}</p>

                            <!-- Email -->
                            <p>@{{ record.currency_rate }}</p>

                            <!-- Actions -->
                            <div class="flex justify-end">
                                @if (bouncer()->hasPermission('settings.exchange_rates.edit'))
                                    <a @click="selectedExchangeRates=1; editModal(record.actions.find(action => action.index === 'edit')?.url)">
                                        <span
                                            :class="record.actions.find(action => action.index === 'edit')?.icon"
                                            class="cursor-pointer rounded-lg p-1.5 text-2xl transition hover:bg-slate-100 dark:hover:bg-slate-800 max-sm:place-self-center"
                                        >
                                        </span>
                                    </a>
                                @endif

                                @if (bouncer()->hasPermission('settings.exchange_rates.delete'))
                                    <a @click="performAction(record.actions.find(action => action.index === 'delete'))">
                                        <span
                                            :class="record.actions.find(action => action.index === 'delete')?.icon"
                                            class="cursor-pointer rounded-lg p-1.5 text-2xl transition hover:bg-slate-100 dark:hover:bg-slate-800 max-sm:place-self-center"
                                        >
                                        </span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </template>
                </template>
                </x-admin::datagrid>

                <!-- Exchange Rate Create Form -->
                <x-admin::form
                v-slot="{ meta, errors, handleSubmit }"
                as="div"
                ref="modalForm"
            >
                <form
                    @submit="handleSubmit($event, updateOrCreate)"
                    ref="exchangeRateCreateForm"
                >
                    <!-- Modal -->
                    <x-admin::modal ref="exchangeRateUpdateOrCreateModal">
                        <!-- Modal Header -->
                        <x-slot:header>
                            <p class="text-lg font-bold text-gray-800 dark:text-white">
                                <span v-if="selectedExchangeRates">
                                    @lang('admin::app.settings.exchange-rates.index.edit.title')
                                </span>

                                <span v-else>
                                    @lang('admin::app.settings.exchange-rates.index.create.title')
                                </span>
                            </p>
                        </x-slot>

                        <!-- Modal Content -->
                        <x-slot:content>
                            {!! view_render_event('bagisto.admin.settings.exchangerate.create.before') !!}

                            <x-admin::form.control-group.control
                                type="hidden"
                                name="id"
                                v-model="selectedExchangeRate.id"
                            />

                            <!-- Currency Code -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('admin::app.settings.exchange-rates.index.create.source-currency')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="base_currency"
                                    disabled
                                    :value="core()->getBaseCurrencyCode()"
                                />
                            </x-admin::form.control-group>

                            <!-- Target Currency -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.settings.exchange-rates.index.create.target-currency')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="select"
                                    name="target_currency"
                                    rules="required"
                                    v-model="selectedExchangeRate.target_currency"
                                    :label="trans('admin::app.settings.exchange-rates.index.create.target-currency')"
                                >
                                    <!-- Default Option -->
                                    <option value="">
                                        @lang('admin::app.settings.exchange-rates.index.create.select-target-currency')
                                    </option>

                                    <option
                                        v-for="currency in currencies"
                                        :value="currency.id"
                                        :selected="currency.id == selectedExchangeRate.target_currency"
                                        v-show="currency.code !== '{{ core()->getBaseCurrencyCode() }}'"
                                    >
                                        @{{ currency.name }}
                                    </option>
                                </x-admin::form.control-group.control>

                                <x-admin::form.control-group.error control-name="target_currency" />
                            </x-admin::form.control-group>

                            <!-- Rate -->
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label class="required">
                                    @lang('admin::app.settings.exchange-rates.index.create.rate')
                                </x-admin::form.control-group.label>

                                <x-admin::form.control-group.control
                                    type="text"
                                    name="rate"
                                    rules="required"
                                    :value="old('rate')"
                                    v-model="selectedExchangeRate.rate"
                                    :label="trans('admin::app.settings.exchange-rates.index.create.rate')"
                                    :placeholder="trans('admin::app.settings.exchange-rates.index.create.rate')"
                                />

                                <x-admin::form.control-group.error control-name="rate" />
                            </x-admin::form.control-group>
                        </x-slot>

                        <!-- Modal Footer -->
                        <x-slot:footer>
                            <!-- Save Button -->
                            <x-admin::button
                                button-type="button"
                                class="primary-button"
                                :title="trans('admin::app.settings.exchange-rates.index.create.save-btn')"
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
            app.component('v-exchange-rates', {
                template: '#v-exchange-rates-template',


                data() {
                    return {
                        selectedExchangeRate: {},

                        selectedExchangeRates: 0,

                        currencies: @json($currencies),

                        isLoading: false,
                    }
                },

                computed: {
                    gridsCount() {
                        let count = this.$refs.datagrid.available.columns.length;

                        if (this.$refs.datagrid.available.actions.length) {
                            ++count;
                        }

                        if (this.$refs.datagrid.available.massActions.length) {
                            ++count;
                        }

                        return count;
                    },
                },

                methods: {
                    updateOrCreate(params, { resetForm, setErrors }) {
                        this.isLoading = true;

                        let formData = new FormData(this.$refs.exchangeRateCreateForm);

                        if (params.id) {
                            formData.append('_method', 'put');
                        }

                        this.$axios.post(params.id ? "{{ route('admin.settings.exchange_rates.update')  }}" : "{{ route('admin.settings.exchange_rates.store')  }}", formData)
                            .then((response) => {
                                this.isLoading = false;

                                this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });

                                this.$refs.exchangeRateUpdateOrCreateModal.close();

                                this.$refs.datagrid.get();

                                resetForm();
                            })
                            .catch(error => {
                                this.isLoading = false;

                                if (error.response.status == 422) {
                                    setErrors(error.response.data.errors);
                                }
                            });
                    },

                    editModal(url) {
                        this.$axios.get(url)
                            .then((response) => {
                                this.selectedExchangeRate = response.data.data.exchangeRate;

                                this.$refs.exchangeRateUpdateOrCreateModal.toggle();
                            })
                            .catch(error => this.$emitter.emit('add-flash', {
                                type: 'error', message: error.response.data.message
                            }));
                    },

                    resetForm() {
                        this.selectedExchangeRate = {};
                    }
                }
            })
        </script>
    @endPushOnce
</x-admin::layouts>
