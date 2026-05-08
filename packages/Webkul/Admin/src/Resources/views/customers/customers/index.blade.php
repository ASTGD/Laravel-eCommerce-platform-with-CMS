<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.customers.customers.index.title')
    </x-slot>

    <div class="space-y-8 bg-transparent pb-8" style="background-color: #f5f5f5;">
        <section class="flex flex-col gap-4 pt-1 sm:flex-row sm:items-center sm:justify-between">
            <h1 class="text-3xl font-semibold tracking-tight text-slate-950 md:text-4xl dark:text-white">
                @lang('admin::app.customers.customers.index.title')
            </h1>

            <div class="flex flex-wrap items-center gap-2.5 max-sm:w-full">
                <!-- Export Modal -->
                <x-admin::datagrid.export src="{{ route('admin.customers.customers.index') }}" />

                <!-- Included customer create blade file -->
                @if (bouncer()->hasPermission('customers.customers.create'))
                    {!! view_render_event('bagisto.admin.customers.customers.create.before') !!}

                    @include('admin::customers.customers.index.create')

                    <v-create-customer-form
                        ref="createCustomerComponent"
                        @customer-created="$refs.customerDatagrid.get()"
                    ></v-create-customer-form>

                    {!! view_render_event('bagisto.admin.customers.customers.create.after') !!}

                    <button
                        class="primary-button !rounded-xl !px-4 !py-2 !text-sm !shadow-sm !shadow-blue-200/60"
                        @click="$refs.createCustomerComponent.openModal()"
                    >
                        @lang('admin::app.customers.customers.index.create.create-btn')
                    </button>
                @endif
            </div>
        </section>

        {!! view_render_event('bagisto.admin.customers.customers.list.before') !!}

        <x-admin::datagrid
            class="customers-modern-datagrid"
            :src="route('admin.customers.customers.index')"
            ref="customerDatagrid"
            :isMultiRow="true"
        >
        @php
            $hasPermission = bouncer()->hasPermission('customers.customers.edit') || bouncer()->hasPermission('customers.customers.delete');
        @endphp

        <template #header="{
            isLoading,
            available,
            applied,
            selectAll,
            sort,
            performAction
        }">
            <template v-if="isLoading">
                <x-admin::shimmer.datagrid.table.head :isMultiRow="true" />
            </template>

            <template v-else>
                <div class="row grid min-w-full grid-cols-1 grid-rows-1 items-center gap-1 border-b border-slate-100 bg-slate-50/80 px-5 py-4 md:grid-cols-[2fr_1fr_1fr] dark:border-slate-800 dark:bg-slate-950/40">
                    <div
                        class="flex select-none items-center gap-2.5"
                        v-for="(columnGroup, index) in [['full_name', 'email', 'phone'], ['status', 'gender', 'group', 'customer_id', 'channel_id'], ['revenue', 'order_count', 'address_count']]"
                    >
                        @if ($hasPermission)
                            <label
                                class="flex w-max cursor-pointer select-none items-center gap-1"
                                for="mass_action_select_all_records"
                                v-if="! index"
                            >
                                <input
                                    type="checkbox"
                                    name="mass_action_select_all_records"
                                    id="mass_action_select_all_records"
                                    class="peer hidden"
                                    :checked="['all', 'partial'].includes(applied.massActions.meta.mode)"
                                    @change="selectAll"
                                >

                                <span
                                    class="icon-uncheckbox cursor-pointer rounded-md text-2xl"
                                    :class="[
                                        applied.massActions.meta.mode === 'all' ? 'peer-checked:icon-checked peer-checked:text-blue-600' : (
                                            applied.massActions.meta.mode === 'partial' ? 'peer-checked:icon-checkbox-partial peer-checked:text-blue-600' : ''
                                        ),
                                    ]"
                                >
                                </span>
                            </label>
                        @endif

                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <span class="[&>*]:after:content-['_/_']">
                                <template v-for="column in columnGroup">
                                    <span
                                        class="after:content-['/'] last:after:content-['']"
                                        :class="{
                                            'font-semibold text-slate-950 dark:text-white': applied.sort.column == column,
                                            'cursor-pointer hover:text-slate-950 dark:hover:text-white': available.columns.find(columnTemp => columnTemp.index === column)?.sortable,
                                        }"
                                        @click="
                                            available.columns.find(columnTemp => columnTemp.index === column)?.sortable ? sort(available.columns.find(columnTemp => columnTemp.index === column)): {}
                                        "
                                    >
                                        @{{ available.columns.find(columnTemp => columnTemp.index === column)?.label }}
                                    </span>
                                </template>
                            </span>

                            <i
                                class="align-text-bottom text-base text-slate-600 dark:text-slate-300 ltr:ml-1.5 rtl:mr-1.5"
                                :class="[applied.sort.order === 'asc' ? 'icon-down-stat': 'icon-up-stat']"
                                v-if="columnGroup.includes(applied.sort.column)"
                            ></i>
                        </p>
                    </div>
                </div>
            </template>
        </template>

        <template #body="{
            isLoading,
            available,
            applied,
            selectAll,
            sort,
            performAction
        }">
            <template v-if="isLoading">
                <x-admin::shimmer.datagrid.table.body :isMultiRow="true" />
            </template>

            <template v-else>
                <div
                    class="row grid min-w-full grid-cols-1 gap-2 border-b border-slate-100 px-4 py-3 transition hover:bg-slate-50/80 md:grid-cols-[minmax(150px,_2fr)_1fr_1fr] md:gap-0 dark:border-slate-800 dark:hover:bg-slate-800/60"
                    v-for="record in available.records"
                >
                    <div class="flex gap-2.5">
                        @if ($hasPermission)
                            <input
                                type="checkbox"
                                :name="`mass_action_select_record_${record.customer_id}`"
                                :id="`mass_action_select_record_${record.customer_id}`"
                                :value="record.customer_id"
                                class="peer hidden"
                                v-model="applied.massActions.indices"
                                @change="setCurrentSelectionMode"
                            >

                            <label
                                class="icon-uncheckbox peer-checked:icon-checked cursor-pointer rounded-md text-2xl peer-checked:text-blue-600"
                                :for="`mass_action_select_record_${record.customer_id}`"
                            >
                            </label>
                        @endif

                        <div class="flex flex-col gap-1.5">
                            <p class="text-base font-semibold text-gray-800 dark:text-white">
                                @{{ record.full_name }}
                            </p>

                            <p class="text-gray-600 dark:text-gray-300">
                                @{{ record.email }}
                            </p>

                            <p class="text-gray-600 dark:text-gray-300">
                                @{{ record.phone ?? 'N/A' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-1.5 ps-8 md:ps-0">
                        <div class="flex gap-1.5">
                            <span
                                :class="{
                                    'label-canceled': record.status == '',
                                    'label-active': record.status === 1,
                                }"
                            >
                                @{{ record.status ? '@lang('admin::app.customers.customers.index.datagrid.active')' : '@lang('admin::app.customers.customers.index.datagrid.inactive')' }}
                            </span>

                            <span
                                :class="{
                                    'label-canceled': record.is_suspended === 1,
                                }"
                            >
                                @{{ record.is_suspended ?  '@lang('admin::app.customers.customers.index.datagrid.suspended')' : '' }}
                            </span>
                        </div>

                        <p class="text-gray-600 dark:text-gray-300">
                            @{{ record.gender ?? 'N/A' }}
                        </p>

                        <p class="text-gray-600 dark:text-gray-300">
                            @{{ record.group ?? 'N/A' }}
                        </p>

                        <p class="text-gray-600 dark:text-gray-300">
                            @lang('admin::app.customers.customers.index.datagrid.channel') - @{{ record.channel_id ? record.channel_id : 'N/A' }}
                        </p>

                        <p class="text-gray-600 dark:text-gray-300">
                            @{{ "@lang('admin::app.customers.customers.index.datagrid.id-value')".replace(':id', record.customer_id) }}
                        </p>
                    </div>

                    <div class="flex items-center justify-between gap-x-4 ps-8 md:ps-0">
                        <div class="flex flex-col gap-1.5">
                            <p class="text-base font-semibold text-gray-800 dark:text-white">
                                @{{ $admin.formatPrice(record.revenue) }}
                            </p>

                            <p class="text-gray-600 dark:text-gray-300">
                                @{{ "@lang('admin::app.customers.customers.index.datagrid.order')".replace(':order', record.order_count) }}
                            </p>

                            <p class="text-gray-600 dark:text-gray-300">
                                @{{ "@lang('admin::app.customers.customers.index.datagrid.address')".replace(':address', record.address_count) }}
                            </p>
                        </div>

                        <div class="flex items-center">
                            <a
                                class="icon-login cursor-pointer rounded-lg p-1.5 text-2xl transition hover:bg-slate-100 dark:hover:bg-slate-800 ltr:ml-1 rtl:mr-1"
                                :href="'{{ route('admin.customers.customers.login_as_customer', ':id') }}'.replace(':id', record.customer_id)"
                                target="_blank"
                            >
                            </a>

                            <a
                                class="icon-sort-right rtl:icon-sort-left cursor-pointer rounded-lg p-1.5 text-2xl transition hover:bg-slate-100 dark:hover:bg-slate-800 ltr:ml-1 rtl:mr-1"
                                :href="'{{ route('admin.customers.customers.view', ':id') }}'.replace(':id', record.customer_id)"
                            >
                            </a>
                        </div>
                    </div>
                </div>
            </template>
        </template>
        </x-admin::datagrid>

        {!! view_render_event('bagisto.admin.customers.customers.list.after') !!}
    </div>

    @pushOnce('styles')
        <style>
            .customers-modern-datagrid > .mt-7 {
                margin-top: 0;
            }

            .customers-modern-datagrid > .mt-4 {
                margin-top: 1rem;
            }

            .customers-modern-datagrid .table-responsive.box-shadow {
                border: 0;
                border-radius: 1.25rem;
                box-shadow: 0 1px 2px 0 rgb(148 163 184 / 0.18);
                background: #ffffff;
                overflow: hidden;
            }

            .dark .customers-modern-datagrid .table-responsive.box-shadow {
                background: rgb(31 41 55);
            }
        </style>
    @endPushOnce
</x-admin::layouts>
