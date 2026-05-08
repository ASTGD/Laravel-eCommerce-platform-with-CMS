<!-- Today's Orders Vue Component -->
<v-dashboard-todays-details>
    <x-admin::shimmer.dashboard.todays-details />
</v-dashboard-todays-details>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-dashboard-todays-details-template"
    >
        <template v-if="isLoading">
            <div class="overflow-hidden rounded-[1.25rem] bg-white p-6 shadow-sm shadow-slate-200/60 dark:bg-gray-800 dark:shadow-none" style="border-radius: 1.25rem;">
                <x-admin::shimmer.dashboard.todays-details />
            </div>
        </template>

        <template v-else>
            <article class="h-full overflow-hidden rounded-[1.25rem] bg-white shadow-sm shadow-slate-200/60 dark:bg-gray-800 dark:shadow-none" style="border-radius: 1.25rem;">
                <div class="border-b border-slate-200 px-6 py-5 dark:border-gray-700">
                    <h3 class="font-sans text-lg leading-7 font-semibold tracking-normal text-slate-950 dark:text-white">
                        Today's Orders
                    </h3>
                </div>

                <div class="grid gap-3 border-b border-slate-200 px-6 py-5 sm:grid-cols-3 xl:grid-cols-1 2xl:grid-cols-3 dark:border-gray-700">
                    <div
                        v-for="item in summaryCards"
                        :key="item.label"
                        class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800"
                    >
                        <p class="font-sans text-sm leading-5 font-medium tracking-normal text-slate-600 dark:text-slate-300">
                            @{{ item.label }}
                        </p>

                        <p class="mt-2 text-lg font-semibold text-slate-950 dark:text-white">
                            @{{ item.value }}
                        </p>
                    </div>
                </div>

                <div
                    v-if="orders.length"
                    class="divide-y divide-slate-200 dark:divide-slate-800"
                >
                    <a
                        v-for="order in orders"
                        :key="order.id"
                        :href="'{{ route('admin.sales.orders.view', ':id') }}'.replace(':id', order.id)"
                        class="flex items-center justify-between gap-4 px-6 py-4 transition hover:bg-slate-50/80 dark:hover:bg-slate-800/60"
                    >
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-semibold text-slate-950 dark:text-white">
                                    @{{ "@lang('admin::app.dashboard.index.order-id', ['id' => ':replace'])".replace(':replace', order.increment_id) }}
                                </p>

                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold"
                                    :class="'label-' + order.status"
                                >
                                    @{{ order.status_label }}
                                </span>
                            </div>

                            <p class="mt-1 truncate text-sm text-slate-500 dark:text-slate-400">
                                @{{ order.customer_name }} · @{{ order.payment_method }}
                            </p>
                        </div>

                        <div class="shrink-0 text-right">
                            <p class="font-semibold text-slate-950 dark:text-white">
                                @{{ order.formatted_base_grand_total }}
                            </p>

                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                @{{ order.created_at }}
                            </p>
                        </div>
                    </a>
                </div>

                <div
                    v-else
                    class="grid justify-center justify-items-center gap-3 px-6 py-12 text-center"
                >
                    <img
                        src="{{ bagisto_asset('images/empty-placeholders/customers.svg') }}"
                        class="h-16 w-16 dark:mix-blend-exclusion dark:invert"
                    />

                    <div class="flex flex-col items-center">
                        <p class="text-base font-semibold text-slate-500 dark:text-slate-300">
                            No orders today
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
                    report: {
                        statistics: {
                            total_sales: {},
                            total_orders: {},
                            total_customers: {},
                            orders: [],
                        },
                    },

                    isLoading: true,
                }
            },

            computed: {
                orders() {
                    return (this.report.statistics?.orders ?? []).slice(0, 4);
                },

                summaryCards() {
                    const stats = this.report.statistics ?? {};

                    return [
                        {
                            label: "@lang('admin::app.dashboard.index.today-sales')",
                            value: stats.total_sales?.formatted_total ?? '$0.00',
                        },
                        {
                            label: "@lang('admin::app.dashboard.index.today-orders')",
                            value: stats.total_orders?.current ?? 0,
                        },
                        {
                            label: "@lang('admin::app.dashboard.index.today-customers')",
                            value: stats.total_customers?.current ?? 0,
                        },
                    ];
                },
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
