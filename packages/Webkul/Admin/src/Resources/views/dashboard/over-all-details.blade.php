<!-- Executive KPI Vue Component -->
<v-dashboard-overall-details>
    <x-admin::shimmer.dashboard.over-all-details />
</v-dashboard-overall-details>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-dashboard-overall-details-template"
    >
        <template v-if="isLoading">
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <x-admin::shimmer.dashboard.over-all-details />
            </div>
        </template>

        <template v-else>
            <div class="dashboard-kpi-grid min-w-0">
                <article
                    v-for="card in cards"
                    :key="card.label"
                    class="dashboard-kpi-card relative min-w-0 overflow-hidden rounded-2xl border bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:bg-gray-900"
                    :style="{ '--dashboard-kpi-color': card.color }"
                >
                    <div class="dashboard-kpi-accent"></div>

                    <div class="relative flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <h3 class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                @{{ card.label }}
                            </h3>

                            <div class="dashboard-kpi-value mt-3 text-gray-950 dark:text-white">
                                @{{ card.value }}
                            </div>

                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <span
                                    v-if="card.badgeText"
                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold leading-none"
                                    :class="card.badgeClass"
                                >
                                    <span
                                        v-if="card.trendIcon"
                                        :class="card.trendIcon"
                                        class="mr-1 text-sm"
                                    ></span>

                                    @{{ card.badgeText }}
                                </span>

                                <span class="text-sm leading-5 text-gray-500 dark:text-gray-400">
                                    @{{ card.helper }}
                                </span>
                            </div>
                        </div>

                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ring-1"
                            :class="card.iconBadgeClass"
                        >
                            <span
                                class="dashboard-kpi-icon text-xl"
                                :class="card.iconClass"
                                aria-hidden="true"
                            ></span>
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
                    report: {
                        statistics: {},
                    },

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
                            helper: 'Revenue in selected range',
                            change: stats.total_sales?.progress,
                            color: '#00A4EF',
                            iconClass: 'icon-sales',
                            iconBadgeClass: 'bg-[#00A4EF]/12 text-[#007db7] ring-[#00A4EF]/28 dark:bg-[#00A4EF]/18 dark:text-[#8ddcff] dark:ring-[#00A4EF]/40',
                        }),
                        this.metric({
                            label: "@lang('admin::app.dashboard.index.total-orders')",
                            value: stats.total_orders?.current ?? 0,
                            helper: 'Orders placed in range',
                            change: stats.total_orders?.progress,
                            color: '#7FBA00',
                            iconClass: 'icon-cart',
                            iconBadgeClass: 'bg-[#7FBA00]/12 text-[#5f8c00] ring-[#7FBA00]/28 dark:bg-[#7FBA00]/18 dark:text-[#b7e56a] dark:ring-[#7FBA00]/40',
                        }),
                        this.metric({
                            label: "@lang('admin::app.dashboard.index.average-sale')",
                            value: stats.avg_sales?.formatted_total ?? '$0.00',
                            helper: 'Average order value',
                            change: stats.avg_sales?.progress,
                            color: '#FFB900',
                            iconClass: 'icon-up-stat',
                            iconBadgeClass: 'bg-[#FFB900]/14 text-[#8a6400] ring-[#FFB900]/32 dark:bg-[#FFB900]/20 dark:text-[#ffd766] dark:ring-[#FFB900]/45',
                        }),
                        this.metric({
                            label: 'New Customers',
                            value: stats.total_customers?.current ?? 0,
                            helper: 'Customer accounts created',
                            change: stats.total_customers?.progress,
                            color: '#737373',
                            iconClass: 'icon-customer-2',
                            iconBadgeClass: 'bg-[#737373]/10 text-[#5f5f5f] ring-[#737373]/24 dark:bg-[#737373]/20 dark:text-[#d4d4d4] dark:ring-[#737373]/40',
                        }),
                        this.metric({
                            label: "@lang('admin::app.dashboard.index.total-unpaid-invoices')",
                            value: stats.total_unpaid_invoices?.formatted_total ?? '$0.00',
                            helper: 'Invoice amount pending',
                            badgeText: 'Review',
                            color: '#F25022',
                            iconClass: 'icon-information',
                            iconBadgeClass: 'bg-[#F25022]/12 text-[#aa3214] ring-[#F25022]/28 dark:bg-[#F25022]/18 dark:text-[#ffab92] dark:ring-[#F25022]/40',
                            tone: 'rose',
                        }),
                        this.metric({
                            label: 'To Ship',
                            value: stats.to_ship?.current ?? 0,
                            helper: 'Orders ready for shipping',
                            badgeText: 'Queue',
                            color: '#00A4EF',
                            iconClass: 'icon-ship',
                            iconBadgeClass: 'bg-[#00A4EF]/12 text-[#007db7] ring-[#00A4EF]/28 dark:bg-[#00A4EF]/18 dark:text-[#8ddcff] dark:ring-[#00A4EF]/40',
                            tone: 'blue',
                        }),
                        this.metric({
                            label: 'In Delivery',
                            value: stats.in_delivery?.current ?? 0,
                            helper: 'Shipments currently moving',
                            badgeText: 'Active',
                            color: '#FFB900',
                            iconClass: 'icon-done',
                            iconBadgeClass: 'bg-[#FFB900]/14 text-[#8a6400] ring-[#FFB900]/32 dark:bg-[#FFB900]/20 dark:text-[#ffd766] dark:ring-[#FFB900]/45',
                            tone: 'amber',
                        }),
                        this.metric({
                            label: 'COD Receivable',
                            value: stats.cod_receivable?.formatted_total ?? '$0.00',
                            helper: 'Cash awaiting collection',
                            badgeText: 'Collect',
                            color: '#7FBA00',
                            iconClass: 'icon-report',
                            iconBadgeClass: 'bg-[#7FBA00]/12 text-[#5f8c00] ring-[#7FBA00]/28 dark:bg-[#7FBA00]/18 dark:text-[#b7e56a] dark:ring-[#7FBA00]/40',
                            tone: 'orange',
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
                    const hasProgress = card.change !== null && card.change !== undefined;
                    const progress = Number(card.change ?? 0);
                    const positive = progress >= 0;

                    return {
                        ...card,
                        badgeText: hasProgress
                            ? `${positive ? '+' : '-'}${Math.abs(progress).toFixed(1)}%`
                            : card.badgeText,
                        badgeClass: hasProgress
                            ? (positive
                                ? 'bg-[#7FBA00]/12 text-[#5f8c00] dark:bg-[#7FBA00]/18 dark:text-[#b7e56a]'
                                : 'bg-[#F25022]/12 text-[#aa3214] dark:bg-[#F25022]/18 dark:text-[#ffab92]')
                            : (card.badgeClass ?? this.badgeClass(card.tone)),
                        trendIcon: hasProgress ? (positive ? 'icon-up-stat' : 'icon-down-stat') : null,
                        iconClass: card.iconClass,
                    };
                },

                badgeClass(tone) {
                    return {
                        blue: 'bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300',
                        amber: 'bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
                        rose: 'bg-rose-50 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300',
                        orange: 'bg-orange-50 text-orange-700 dark:bg-orange-500/15 dark:text-orange-300',
                    }[tone] ?? 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300';
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

@pushOnce('styles')
    <style>
        .dashboard-kpi-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 1rem;
        }

        .dashboard-kpi-card {
            border-color: color-mix(in srgb, var(--dashboard-kpi-color) 30%, rgb(229 231 235));
            box-shadow:
                inset 0 0 0 1px color-mix(in srgb, var(--dashboard-kpi-color) 10%, transparent),
                0 1px 2px rgb(15 23 42 / 0.04);
        }

        .dark .dashboard-kpi-card {
            border-color: color-mix(in srgb, var(--dashboard-kpi-color) 42%, rgb(31 41 55));
        }

        .dashboard-kpi-accent {
            position: absolute;
            inset: 0 auto 0 0;
            width: 0.38rem;
            background: var(--dashboard-kpi-color);
        }

        .dashboard-kpi-value {
            font-size: 1.5rem;
            font-weight: 600;
            letter-spacing: -0.025em;
            line-height: 1.15 !important;
        }

        .dashboard-kpi-icon {
            color: var(--dashboard-kpi-color) !important;
        }

        @media (min-width: 640px) {
            .dashboard-kpi-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1280px) {
            .dashboard-kpi-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }
    </style>
@endPushOnce
