<!-- Over Details Vue Component -->
<v-dashboard-overall-details>
    <!-- Shimmer -->
    <x-admin::shimmer.dashboard.over-all-details />
</v-dashboard-overall-details>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-dashboard-overall-details-template"
    >
        <template v-if="isLoading">
            <div class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                <x-admin::shimmer.dashboard.over-all-details />
            </div>
        </template>

        <template v-else>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                <article
                    v-for="card in cards"
                    :key="card.label"
                    class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60 transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-slate-200/80 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none dark:hover:shadow-none"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-3">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-2xl text-white shadow-sm shadow-slate-900/10"
                                :class="card.iconBoxClass"
                            >
                                <img :src="card.icon" class="h-6 w-6" :alt="card.label">
                            </div>

                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">
                                    @{{ card.label }}
                                </p>

                                <p class="mt-2 text-2xl font-semibold tracking-tight text-slate-950 dark:text-white">
                                    @{{ card.value }}
                                </p>

                                <p class="mt-1 text-sm leading-6 text-slate-500 dark:text-slate-400">
                                    @{{ card.help }}
                                </p>
                            </div>
                        </div>

                        <span
                            class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold"
                            :class="card.trendClass"
                        >
                            <span :class="card.trendIcon" class="mr-1.5 text-sm"></span>
                            @{{ card.change }}
                        </span>
                    </div>
                </article>
            </div>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-overall-details', {
            template: '#v-dashboard-overall-details-template',

            data() {
                return {
                    report: [],

                    isLoading: true,
                }
            },

            computed: {
                cards() {
                    const stats = this.report.statistics ?? {};

                    return [
                        this.metric({
                            label: "@lang('admin::app.dashboard.index.total-sales')",
                            value: stats.total_sales?.formatted_total ?? '$0.00',
                            help: "@lang('admin::app.dashboard.index.total-sales')",
                            change: stats.total_sales?.progress,
                            icon: "{{ bagisto_asset('images/total-sales.svg') }}",
                            iconBoxClass: 'bg-slate-950',
                        }),
                        this.metric({
                            label: "@lang('admin::app.dashboard.index.total-orders')",
                            value: stats.total_orders?.current ?? 0,
                            help: "@lang('admin::app.dashboard.index.order-count', ['count' => ':count'])".replace(':count', stats.total_orders?.current ?? 0),
                            change: stats.total_orders?.progress,
                            icon: "{{ bagisto_asset('images/total-orders.svg') }}",
                            iconBoxClass: 'bg-blue-600',
                        }),
                        this.metric({
                            label: "@lang('admin::app.dashboard.index.total-customers')",
                            value: stats.total_customers?.current ?? 0,
                            help: "@lang('admin::app.dashboard.index.total-customers')",
                            change: stats.total_customers?.progress,
                            icon: "{{ bagisto_asset('images/customers.svg') }}",
                            iconBoxClass: 'bg-emerald-600',
                        }),
                        this.metric({
                            label: "@lang('admin::app.dashboard.index.average-sale')",
                            value: stats.avg_sales?.formatted_total ?? '$0.00',
                            help: "@lang('admin::app.dashboard.index.average-sale')",
                            change: stats.avg_sales?.progress,
                            icon: "{{ bagisto_asset('images/average-orders.svg') }}",
                            iconBoxClass: 'bg-amber-500',
                        }),
                        this.metric({
                            label: "@lang('admin::app.dashboard.index.total-unpaid-invoices')",
                            value: stats.total_unpaid_invoices?.formatted_total ?? '$0.00',
                            help: "@lang('admin::app.dashboard.index.total-unpaid-invoices')",
                            change: stats.total_unpaid_invoices?.progress,
                            icon: "{{ bagisto_asset('images/unpaid-invoices.svg') }}",
                            iconBoxClass: 'bg-rose-500',
                        }),
                    ];
                },
            },

            mounted() {
                this.getStats({});

                this.$emitter.on('reporting-filter-updated', this.getStats);
            },

            methods: {
                metric(card) {
                    const progress = Number(card.change ?? 0);
                    const positive = progress >= 0;

                    return {
                        ...card,
                        change: `${positive ? '+' : '-'}${Math.abs(progress).toFixed(2)}%`,
                        trendClass: positive
                            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300'
                            : 'bg-rose-50 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300',
                        trendIcon: positive ? 'icon-up-stat' : 'icon-down-stat',
                    };
                },

                getStats(filters) {
                    this.isLoading = true;

                    var filters = Object.assign({}, filters);

                    filters.type = 'over-all';

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
