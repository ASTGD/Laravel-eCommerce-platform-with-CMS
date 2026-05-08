<!-- Shipment Operations Trend Vue Component -->
<v-dashboard-operations-trend>
    <x-admin::shimmer.dashboard.total-sales />
</v-dashboard-operations-trend>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-dashboard-operations-trend-template"
    >
        <template v-if="isLoading">
            <div class="overflow-hidden rounded-[1.25rem] bg-white p-6 shadow-sm shadow-slate-200/60 dark:bg-gray-800 dark:shadow-none" style="border-radius: 1.25rem;">
                <x-admin::shimmer.dashboard.total-sales />
            </div>
        </template>

        <template v-else>
            <article class="overflow-hidden rounded-[1.25rem] bg-white shadow-sm shadow-slate-200/60 dark:bg-gray-800 dark:shadow-none" style="border-radius: 1.25rem;">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5 dark:border-gray-700">
                    <h3 class="font-sans text-lg leading-7 font-semibold tracking-normal text-slate-950 dark:text-white">
                        Courier Movement
                    </h3>
                </div>

                <div class="px-6 py-6">
                    <div class="h-[320px] min-h-[320px] max-sm:h-[280px] max-sm:min-h-[280px]">
                        <x-admin::charts.bar
                            ::labels="chartLabels"
                            ::datasets="chartDatasets"
                            ::aspect-ratio="2.1"
                            ::show-legend="true"
                            ::fluid-height="true"
                            ::show-all-x-ticks="true"
                        />
                    </div>
                </div>
            </article>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-operations-trend', {
            template: '#v-dashboard-operations-trend-template',

            data() {
                return {
                    report: {
                        statistics: {
                            labels: [],
                            created: [],
                            handed_over: [],
                            delivered: [],
                            summary: {},
                        },
                    },

                    isLoading: true,
                }
            },

            computed: {
                chartLabels() {
                    return this.report.statistics?.labels ?? [];
                },

                chartDatasets() {
                    const stats = this.report.statistics ?? {};
                    const toBarValue = (value) => {
                        const number = Number(value ?? 0);

                        return number > 0 ? number : null;
                    };

                    return [
                        {
                            label: 'Created',
                            data: (stats.created ?? []).map(toBarValue),
                            borderWidth: 0,
                            backgroundColor: '#2f6fed',
                            hoverBackgroundColor: '#1d4ed8',
                            borderRadius: {
                                topLeft: 8,
                                topRight: 8,
                            },
                            borderSkipped: 'bottom',
                            barThickness: 8,
                            maxBarThickness: 20,
                            barPercentage: 0.86,
                            categoryPercentage: 0.72,
                        },
                        {
                            label: 'Handed Over',
                            data: (stats.handed_over ?? []).map(toBarValue),
                            borderWidth: 0,
                            backgroundColor: '#ef4444',
                            hoverBackgroundColor: '#dc2626',
                            borderRadius: {
                                topLeft: 8,
                                topRight: 8,
                            },
                            borderSkipped: 'bottom',
                            barThickness: 8,
                            maxBarThickness: 20,
                            barPercentage: 0.86,
                            categoryPercentage: 0.72,
                        },
                        {
                            label: 'Delivered',
                            data: (stats.delivered ?? []).map(toBarValue),
                            borderWidth: 0,
                            backgroundColor: '#14b8a6',
                            hoverBackgroundColor: '#0f766e',
                            borderRadius: {
                                topLeft: 8,
                                topRight: 8,
                            },
                            borderSkipped: 'bottom',
                            barThickness: 8,
                            maxBarThickness: 20,
                            barPercentage: 0.86,
                            categoryPercentage: 0.72,
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

                    filters.type = 'operations-trend';

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
