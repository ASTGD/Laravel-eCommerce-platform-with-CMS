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
            <div class="overflow-hidden rounded-[1.25rem] bg-white p-6 shadow-sm shadow-slate-200/60 dark:bg-slate-900 dark:shadow-none" style="border-radius: 1.25rem;">
                <x-admin::shimmer.dashboard.over-all-details />
            </div>
        </template>

        <template v-else>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <article
                    v-for="card in cards"
                    :key="card.label"
                    class="group rounded-[1.25rem] bg-white p-5 shadow-sm shadow-slate-200/60 transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-slate-200/80 dark:bg-slate-900 dark:shadow-none dark:hover:shadow-none" style="border-radius: 1.25rem;"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex min-w-0 items-start gap-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-slate-200/70 bg-slate-50 shadow-none dark:border-slate-700/60 dark:bg-slate-800/40"
                                :class="[card.iconBoxClass, card.iconTextClass ?? 'text-slate-500 dark:text-slate-300']"
                            >
                                <img
                                    v-if="card.icon"
                                    :src="card.icon"
                                    class="h-5 w-5 opacity-90"
                                    :alt="card.label"
                                >

                                <span
                                    v-else
                                    class="leading-none"
                                    :class="card.iconClass"
                                ></span>
                            </div>

                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">
                                    @{{ card.label }}
                                </p>

                                <p class="mt-2 text-2xl font-semibold tracking-tight text-slate-950 dark:text-white">
                                    @{{ card.value }}
                                </p>
                            </div>
                        </div>

                        <span
                            class="inline-flex shrink-0 items-center rounded-full px-2.5 py-1 text-xs font-semibold"
                            :class="card.badgeClass"
                        >
                            <span
                                v-if="card.trendIcon"
                                :class="card.trendIcon"
                                class="mr-1 text-sm"
                            ></span>

                            @{{ card.badgeText }}
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
                            change: stats.total_sales?.progress,
                            icon: "{{ bagisto_asset('images/total-sales.svg') }}",
                            iconBoxClass: 'border border-slate-200/80 bg-slate-100/95 dark:border-slate-700/60 dark:bg-slate-800/40',
                        }),
                        this.metric({
                            label: "@lang('admin::app.dashboard.index.total-orders')",
                            value: stats.total_orders?.current ?? 0,
                            change: stats.total_orders?.progress,
                            icon: "{{ bagisto_asset('images/total-orders.svg') }}",
                            iconBoxClass: 'border border-blue-200/80 bg-blue-100/95 dark:border-blue-500/20 dark:bg-blue-500/10',
                        }),
                        this.metric({
                            label: "@lang('admin::app.dashboard.index.average-sale')",
                            value: stats.avg_sales?.formatted_total ?? '$0.00',
                            change: stats.avg_sales?.progress,
                            icon: "{{ bagisto_asset('images/average-orders.svg') }}",
                            iconBoxClass: 'border border-amber-200/80 bg-amber-100/95 dark:border-amber-500/20 dark:bg-amber-500/10',
                        }),
                        this.metric({
                            label: 'New Customers',
                            value: stats.total_customers?.current ?? 0,
                            change: stats.total_customers?.progress,
                            icon: "{{ bagisto_asset('images/customers.svg') }}",
                            iconBoxClass: 'border border-emerald-200/80 bg-emerald-100/95 dark:border-emerald-500/20 dark:bg-emerald-500/10',
                        }),
                        this.metric({
                            label: "@lang('admin::app.dashboard.index.total-unpaid-invoices')",
                            value: stats.total_unpaid_invoices?.formatted_total ?? '$0.00',
                            badgeText: 'Review',
                            icon: "{{ bagisto_asset('images/unpaid-invoices.svg') }}",
                            iconBoxClass: 'border border-rose-200/80 bg-rose-100/95 dark:border-rose-500/20 dark:bg-rose-500/10',
                            tone: 'rose',
                        }),
                        this.metric({
                            label: 'To Ship',
                            value: stats.to_ship?.current ?? 0,
                            badgeText: 'Queue',
                            iconClass: 'icon-ship',
                            iconBoxClass: 'border border-sky-200/80 bg-sky-100/95 dark:border-sky-500/20 dark:bg-sky-500/10',
                            iconTextClass: 'text-sky-500 dark:text-sky-300',
                            tone: 'blue',
                        }),
                        this.metric({
                            label: 'In Delivery',
                            value: stats.in_delivery?.current ?? 0,
                            badgeText: 'Active',
                            iconClass: 'icon-processing',
                            iconBoxClass: 'border border-violet-200/80 bg-violet-100/95 dark:border-violet-500/20 dark:bg-violet-500/10',
                            iconTextClass: 'text-amber-500 dark:text-amber-300',
                            tone: 'amber',
                        }),
                        this.metric({
                            label: 'COD Receivable',
                            value: stats.cod_receivable?.formatted_total ?? '$0.00',
                            badgeText: 'Collect',
                            iconClass: 'icon-information',
                            iconBoxClass: 'border border-orange-200/80 bg-orange-100/95 dark:border-orange-500/20 dark:bg-orange-500/10',
                            iconTextClass: 'text-orange-500 dark:text-orange-300',
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
                                ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300'
                                : 'bg-rose-50 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300')
                            : (card.badgeClass ?? this.badgeClass(card.tone)),
                        trendIcon: hasProgress ? (positive ? 'icon-up-stat' : 'icon-down-stat') : null,
                        iconClass: card.iconClass ?? this.iconClass(card.tone),
                    };
                },

                iconClass(tone) {
                    return 'text-lg';
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
