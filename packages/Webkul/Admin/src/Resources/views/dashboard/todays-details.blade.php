<!-- Todays Details Vue Component -->
<v-dashboard-todays-details>
    <!-- Shimmer -->
    <x-admin::shimmer.dashboard.todays-details />
</v-dashboard-todays-details>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-dashboard-todays-details-template"
    >
        <template v-if="isLoading">
            <div class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                <x-admin::shimmer.dashboard.todays-details />
            </div>
        </template>

        <template v-else>
            <article class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm shadow-slate-200/60 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                <div class="flex flex-col gap-4 border-b border-slate-200 px-6 py-6 sm:flex-row sm:items-end sm:justify-between dark:border-slate-800">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">
                            @lang('admin::app.dashboard.index.today-details')
                        </p>

                        <h3 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950 dark:text-white">
                            Daily activity
                        </h3>
                    </div>

                    <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                        @{{ report.date_range }}
                    </span>
                </div>

                <div class="grid gap-3 border-b border-slate-200 px-6 py-6 sm:grid-cols-3 dark:border-slate-800">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">
                            @lang('admin::app.dashboard.index.today-sales')
                        </p>

                        <p class="mt-2 text-lg font-semibold text-slate-950 dark:text-white">
                            @{{ report.statistics.total_sales.formatted_total }}
                        </p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">
                            @lang('admin::app.dashboard.index.today-orders')
                        </p>

                        <p class="mt-2 text-lg font-semibold text-slate-950 dark:text-white">
                            @{{ report.statistics.total_orders.current }}
                        </p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">
                            @lang('admin::app.dashboard.index.today-customers')
                        </p>

                        <p class="mt-2 text-lg font-semibold text-slate-950 dark:text-white">
                            @{{ report.statistics.total_customers.current }}
                        </p>
                    </div>
                </div>

                <div
                    v-if="report.statistics.orders.length"
                    class="divide-y divide-slate-200 dark:divide-slate-800"
                >
                    <div
                        v-for="order in report.statistics.orders"
                        class="flex flex-col gap-4 px-6 py-5 transition hover:bg-slate-50/80 dark:hover:bg-slate-800/60 xl:flex-row xl:items-center xl:justify-between"
                    >
                        <div class="min-w-0 space-y-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-base font-semibold text-slate-950 dark:text-white">
                                    @{{ "@lang('admin::app.dashboard.index.order-id', ['id' => ':replace'])".replace(':replace', order.increment_id) }}
                                </p>

                                <span
                                    class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em]"
                                    :class="'label-' + order.status"
                                >
                                    @{{ order.status_label }}
                                </span>
                            </div>

                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                @{{ order.created_at }}
                            </p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3 xl:min-w-[420px] xl:gap-4">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950">
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">
                                    Amount
                                </p>
                                <p class="mt-2 text-sm font-semibold text-slate-950 dark:text-white">
                                    @{{ order.formatted_base_grand_total }}
                                </p>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950">
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">
                                    Payment
                                </p>
                                <p class="mt-2 text-sm font-semibold text-slate-950 dark:text-white">
                                    @{{ order.payment_method }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    @{{ order.channel_name }}
                                </p>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950">
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">
                                    Customer
                                </p>
                                <p class="mt-2 text-sm font-semibold text-slate-950 dark:text-white">
                                    @{{ order.customer_name }}
                                </p>
                                <p class="mt-1 max-w-[180px] truncate text-xs text-slate-500 dark:text-slate-400">
                                    @{{ order.customer_email }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-else
                    class="grid justify-center justify-items-center gap-3 px-6 py-16 text-center"
                >
                    <img
                        src="{{ bagisto_asset('images/empty-placeholders/customers.svg') }}"
                        class="h-20 w-20 dark:mix-blend-exclusion dark:invert"
                    />

                    <div class="flex flex-col items-center">
                        <p class="text-base font-semibold text-slate-500 dark:text-slate-300">
                            No orders yet
                        </p>

                        <p class="text-sm text-slate-400 dark:text-slate-400">
                            Orders placed in the selected date range will appear here.
                        </p>
                    </div>
                </div>
            </article>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-todays-details', {
            template: '#v-dashboard-todays-details-template',

            data() {
                return {
                    report: [],

                    isLoading: true,
                }
            },

            mounted() {
                this.getStats({});

                this.$emitter.on('reporting-filter-updated', this.getStats);
            },

            methods: {
                getStats(filters) {
                    this.isLoading = true;

                    var filters = Object.assign({}, filters);

                    filters.type = 'today';

                    this.$axios.get("{{ route('admin.dashboard.stats') }}", {
                            params: filters
                        })
                        .then(response => {
                            this.report = response.data;

                            this.isLoading = false;
                        })
                        .catch(error => {});
                }
            }
        });
    </script>
@endPushOnce
