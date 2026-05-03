<!-- Revenue / Orders Trend Vue Component -->
<v-dashboard-total-sales>
    <x-admin::shimmer.dashboard.total-sales />
</v-dashboard-total-sales>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-dashboard-total-sales-template"
    >
        <template v-if="isLoading">
            <div class="overflow-hidden rounded-[1.25rem] bg-white p-6 shadow-sm shadow-slate-200/60 dark:bg-slate-900 dark:shadow-none" style="border-radius: 1.25rem;">
                <x-admin::shimmer.dashboard.total-sales />
            </div>
        </template>

        <template v-else>
            <article class="overflow-hidden rounded-[1.25rem] bg-white shadow-sm shadow-slate-200/60 dark:bg-slate-900 dark:shadow-none" style="border-radius: 1.25rem;">
                <div class="border-b border-slate-200 px-6 py-6 dark:border-slate-800">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">
                            Revenue / Orders Trend
                        </p>

                        <h3 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950 dark:text-white">
                            @{{ report.statistics.total_sales.formatted_total }}
                        </h3>
                    </div>
                </div>

                <div class="px-6 py-6">
                    <div class="h-[320px] min-h-[320px] max-sm:h-[280px] max-sm:min-h-[280px]">
                        <x-admin::charts.line
                            ::labels="chartLabels"
                            ::datasets="chartDatasets"
                            ::aspect-ratio="2.1"
                            ::show-legend="true"
                            ::multi-axis="true"
                            ::fluid-height="true"
                            ::show-all-x-ticks="true"
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
                    report: {
                        statistics: {
                            total_sales: {},
                            over_time: [],
                        },
                    },

                    isLoading: true,
                }
            },

            computed: {
                chartLabels() {
                    return this.report.statistics?.over_time?.map(({ label }) => label) ?? [];
                },

                chartDatasets() {
                    const points = this.report.statistics?.over_time ?? [];

                    return [
                        {
                            label: 'Revenue',
                            data: points.map(({ total }) => Number(total ?? 0)),
                            yAxisID: 'y',
                            tension: 0.35,
                            pointRadius: 0,
                            pointHoverRadius: 5,
                            borderWidth: 2,
                            borderColor: '#0E9CFF',
                            pointBackgroundColor: '#0E9CFF',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 1.5,
                            backgroundColor: 'rgba(14, 156, 255, 0.16)',
                            fill: true,
                        },
                        {
                            label: 'Orders',
                            data: points.map(({ count }) => Number(count ?? 0)),
                            yAxisID: 'y1',
                            tension: 0.35,
                            pointRadius: 0,
                            pointHoverRadius: 5,
                            borderWidth: 2,
                            borderColor: '#34D399',
                            pointBackgroundColor: '#34D399',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 1.5,
                            backgroundColor: 'rgba(52, 211, 153, 0.10)',
                            fill: false,
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
