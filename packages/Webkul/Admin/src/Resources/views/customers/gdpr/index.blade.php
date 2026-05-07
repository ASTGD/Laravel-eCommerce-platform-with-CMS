<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.customers.gdpr.index.title')
    </x-slot>

    <div class="space-y-8 bg-transparent pb-8" style="background-color: #f5f5f5;">
        <section class="flex flex-col gap-4 pt-1 sm:flex-row sm:items-center sm:justify-between">
            <h1 class="text-3xl font-semibold tracking-tight text-slate-950 md:text-4xl dark:text-white">
                @lang('admin::app.customers.gdpr.index.title')
            </h1>

            <div class="flex flex-wrap items-center gap-2.5 max-sm:w-full">
                <x-admin::datagrid.export src="{{ route('admin.customers.gdpr.index') }}" />
            </div>
        </section>

        {!! view_render_event('bagisto.admin.customers.gdpr.list.before') !!}

        <v-create-gdpr></v-create-gdpr>

        {!! view_render_event('bagisto.admin.customers.gdpr.list.after') !!}
    </div>

    @pushOnce('styles')
        <style>
            .customer-gdpr-modern-datagrid > .mt-7 {
                margin-top: 0;
            }

            .customer-gdpr-modern-datagrid > .mt-4 {
                margin-top: 1rem;
            }

            .customer-gdpr-modern-datagrid .table-responsive.box-shadow {
                border: 0;
                border-radius: 1.25rem;
                box-shadow: 0 1px 2px 0 rgb(148 163 184 / 0.18);
                background: #ffffff;
                overflow: hidden;
            }

            .dark .customer-gdpr-modern-datagrid .table-responsive.box-shadow {
                background: rgb(15 23 42);
            }
        </style>
    @endPushOnce

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-create-gdpr-template"
        >
            <div>
                <x-admin::datagrid
                    class="customer-gdpr-modern-datagrid"
                    src="{{ route('admin.customers.gdpr.index') }}"
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
                                <p>@{{ record.id }}</p>

                                <!-- Customer Name -->
                                <p>@{{ record.customer_name }}</p>

                                <!-- Status -->
                                <p v-html="record.status"></p>

                                <!-- Type -->
                                <p>@{{ record.type }}</p>

                                <!-- Message -->
                                <p>@{{ record.message }}</p>

                                <!-- Created At -->
                                <p>@{{ record.created_at }}</p>

                                <!-- Actions -->
                                <div class="flex justify-end">
                                    @if (bouncer()->hasPermission('customers.gdpr_requests.edit'))
                                        <a @click="editModal(record.actions.find(action => action.index === 'edit')?.url, record.id)">
                                            <span
                                                :class="record.actions.find(action => action.index === 'edit')?.icon"
                                                class="cursor-pointer rounded-lg p-1.5 text-2xl transition hover:bg-slate-100 dark:hover:bg-slate-800 max-sm:place-self-center"
                                                :title="record.actions.find(action => action.title === '@lang('admin::app.customers.gdpr.index.datagrid.edit')')?.title"
                                            >
                                            </span>
                                        </a>
                                    @endif

                                    @if (bouncer()->hasPermission('customers.gdpr_requests.delete'))
                                        <a @click="performAction(record.actions.find(action => action.index === 'delete'))">
                                            <span
                                                :class="record.actions.find(action => action.index === 'delete')?.icon"
                                                class="cursor-pointer rounded-lg p-1.5 text-2xl transition hover:bg-slate-100 dark:hover:bg-slate-800 max-sm:place-self-center"
                                                :title="record.actions.find(action => action.title === '@lang('admin::app.customers.gdpr.index.datagrid.delete')')?.title"
                                            >
                                            </span>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </template>
                    </template>
                </x-admin::datagrid>

                {!! view_render_event('bagisto.admin.customers.groups.list.after') !!}

                <!-- Modal Form -->
                <x-admin::form
                    v-slot="{ meta, errors, handleSubmit }"
                    as="div"
                    ref="modalForm"
                >
                    <form
                        @submit="handleSubmit($event, update)"
                        ref="gdprForm"
                    >
                        <!-- Create Group Modal -->
                        <x-admin::modal ref="gdprUpdateModal">
                            <!-- Modal Header -->
                            <x-slot:header>
                                <p class="text-lg font-bold text-gray-800 dark:text-white">
                                    @lang('admin::app.customers.gdpr.index.modal.title')
                                </p>
                            </x-slot>

                            <!-- Modal Content -->
                            <x-slot:content>
                                <!-- Status -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.customers.gdpr.index.modal.status')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="hidden"
                                        name="id"
                                    />

                                    <x-admin::form.control-group.control
                                        type="select"
                                        id="status"
                                        name="status"
                                        rules="required"
                                        :label="trans('admin::app.customers.gdpr.index.modal.status')"
                                        :placeholder="trans('admin::app.customers.gdpr.index.modal.status')"
                                    >
                                        <option value="pending" selected>
                                            @lang('admin::app.customers.gdpr.index.modal.pending')
                                        </option>

                                        <option value="processing">
                                            @lang('admin::app.customers.gdpr.index.modal.processing')
                                        </option>
                                        
                                        <option value="declined">
                                            @lang('admin::app.customers.gdpr.index.modal.declined')
                                        </option>

                                        <option value="completed">
                                            @lang('admin::app.customers.gdpr.index.modal.completed')
                                        </option>

                                        <option value="revoked">
                                            @lang('admin::app.customers.gdpr.index.modal.revoked')
                                        </option>
                                    </x-admin::form.control-group.control>

                                    <x-admin::form.control-group.error control-name="status" />
                                </x-admin::form.control-group>

                                <!-- Type -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.customers.gdpr.index.modal.type')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="hidden"
                                        name="type"
                                    />

                                    <x-admin::form.control-group.control
                                        type="text"
                                        id="type"
                                        name="type"
                                        rules="required"
                                        :label="trans('admin::app.customers.gdpr.index.modal.type')"
                                        :placeholder="trans('admin::app.customers.gdpr.index.modal.type')"
                                        disabled
                                    />

                                    <x-admin::form.control-group.error control-name="type" />
                                </x-admin::form.control-group>

                                <!-- Message -->
                                <x-admin::form.control-group>
                                    <x-admin::form.control-group.label class="required">
                                        @lang('admin::app.customers.gdpr.index.modal.message')
                                    </x-admin::form.control-group.label>

                                    <x-admin::form.control-group.control
                                        type="textarea"
                                        id="message"
                                        name="message"
                                        rules="required"
                                        :label="trans('admin::app.customers.gdpr.index.modal.message')"
                                        :placeholder="trans('admin::app.customers.gdpr.index.modal.message')"
                                    />

                                    <x-admin::form.control-group.error control-name="message" />
                                </x-admin::form.control-group>
                            </x-slot>

                            <!-- Modal Footer -->
                            <x-slot:footer>
                                <div class="flex items-center gap-x-2.5">
                                    <button
                                        type="submit"
                                        class="primary-button"
                                    >
                                        @lang('admin::app.customers.gdpr.index.modal.save-btn')
                                    </button>
                                </div>
                            </x-slot>
                        </x-admin::modal>
                    </form>
                </x-admin::form>
            </div>
        </script>

        <script type="module">
            app.component('v-create-gdpr', {
                template: '#v-create-gdpr-template',

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
                    update(params) {
                        const formData = new FormData(this.$refs.gdprForm);

                        if (params.id) {
                            formData.append('_method', 'put');
                        }

                        this.$axios.post('{{ route('admin.customers.gdpr.update', ':id') }}'.replace(':id', params.id), formData)
                            .then((response) => {
                                this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });
                            })
                            .catch((error) => {
                                this.$emitter.emit('add-flash', { type: 'warning', message: error.response.data.message });
                            })
                            .finally(() => {
                                this.$refs.gdprUpdateModal.close();

                                this.$refs.datagrid.get();
                            });
                    },

                    editModal(url, id) {
                        this.$axios.get(url, { params: { id } })
                            .then((response) => {
                                this.$refs.gdprUpdateModal.toggle();

                                this.$refs.modalForm.setValues(response.data.data);
                            })
                    },
                }
            })
        </script>
    @endPushOnce

</x-admin::layouts>
