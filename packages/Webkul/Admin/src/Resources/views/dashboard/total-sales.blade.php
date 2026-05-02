<!-- Total Sales Vue Component -->
<v-dashboard-total-sales>
    <!-- Shimmer -->
    <x-admin::shimmer.dashboard.total-sales />
</v-dashboard-total-sales>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-dashboard-total-sales-template"
    >
        <template v-if="isLoading">
            <div class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                <x-admin::shimmer.dashboard.total-sales />
            </div>
        </template>

        <template v-else>
            <article class="relative overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm shadow-slate-200/60 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-slate-950 via-blue-600 to-orange-500"></div>

                <div class="flex flex-col gap-4 border-b border-slate-200 px-6 py-6 sm:flex-row sm:items-end sm:justify-between dark:border-slate-800">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">
                            @lang('admin::app.dashboard.index.total-sales')
                        </p>

                        <h3 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950 dark:text-white">
                            @{{ report.statistics.total_sales.formatted_total }}
                        </h3>

                        <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">
                            Orders processed in the selected range.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                            @{{ report.date_range }}
                        </span>

                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                            @lang('admin::app.dashboard.index.total-orders')
                        </span>
                    </div>
                </div>

                <div class="px-6 py-6">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950">
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">
                                @lang('admin::app.dashboard.index.total-sales')
                            </p>

                            <p class="mt-2 text-lg font-semibold text-slate-950 dark:text-white">
                                @{{ report.statistics.total_sales.formatted_total }}
                            </p>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950">
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">
                                @lang('admin::app.dashboard.index.total-orders')
                            </p>

                            <p class="mt-2 text-lg font-semibold text-slate-950 dark:text-white">
                                @{{ report.statistics.total_orders.current }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 rounded-[1.5rem] border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-950">
                        <x-admin::charts.bar
                            ::labels="chartLabels"
                            ::datasets="chartDatasets"
                            ::aspect-ratio="1.92"
                        />
                    </div>
                </div>
            </article>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-total-sales', {
            template: '#v-dashboard-total-sales-template',

            data() {
                return {
                    report: [],

                    isLoading: true,
                }
            },

            computed: {
                chartLabels() {
                    return this.report.statistics?.over_time?.map(({ label }) => label) ?? [];
                },

                chartDatasets() {
                    return [{
                        data: this.report.statistics?.over_time?.map(({ total }) => total) ?? [],
                        barThickness: 6,
                        backgroundColor: '#0f172a',
                    }];
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

                    filters.type = 'total-sales';

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
