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
            <div class="overflow-hidden rounded-[24px] border border-slate-200/70 bg-white p-6 shadow-none dark:border-slate-800 dark:bg-slate-900">
                <x-admin::shimmer.dashboard.over-all-details />
            </div>
        </template>

        <template v-else>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <article
                    v-for="card in cards"
                    :key="card.label"
                    class="group relative min-h-[136px] rounded-[24px] border border-slate-200/70 bg-white p-5 shadow-none transition-colors duration-200 hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0 space-y-2.5 pr-16">
                            <h3 class="font-sans text-[13px] leading-5 font-medium tracking-normal text-slate-500 dark:text-slate-400">
                                @{{ card.label }}
                            </h3>

                            <span class="font-sans text-[26px] leading-8 font-bold tracking-tight text-slate-950 dark:text-white">
                                @{{ card.value }}
                            </span>
                        </div>

                        <div
                            class="relative top-1 flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-slate-300 bg-white shadow-none dark:border-slate-700 dark:bg-slate-800/60"
                            :class="card.iconBoxClass"
                        >
                            <img
                                v-if="card.icon"
                                :src="card.icon"
                                class="h-6 w-6 opacity-100"
                                :alt="card.label"
                            >

                            <span
                                v-else
                                class="text-[22px] leading-none"
                                :class="[card.iconClass, card.iconTextClass ?? '!text-slate-600 dark:!text-slate-300']"
                            ></span>
                        </div>
                    </div>

                    <span
                        class="absolute bottom-5 left-5 inline-flex shrink-0 items-center rounded-full px-3 py-1 text-[12px] font-semibold"
                        :class="card.badgeClass"
                    >
                        <span
                            v-if="card.trendIcon"
                            :class="card.trendIcon"
                            class="mr-1 text-sm"
                        ></span>

                        @{{ card.badgeText }}
                    </span>
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
                            iconBoxClass: '!border-slate-300 !bg-slate-100 dark:!border-slate-700 dark:!bg-slate-800/60',
                        }),
                        this.metric({
                            label: "@lang('admin::app.dashboard.index.total-orders')",
                            value: stats.total_orders?.current ?? 0,
                            change: stats.total_orders?.progress,
                            icon: "{{ bagisto_asset('images/total-orders.svg') }}",
                            iconBoxClass: '!border-blue-200 !bg-blue-50 dark:!border-blue-500/30 dark:!bg-blue-500/15',
                        }),
                        this.metric({
                            label: "@lang('admin::app.dashboard.index.average-sale')",
                            value: stats.avg_sales?.formatted_total ?? '$0.00',
                            change: stats.avg_sales?.progress,
                            icon: "{{ bagisto_asset('images/average-orders.svg') }}",
                            iconBoxClass: '!border-amber-200 !bg-amber-50 dark:!border-amber-500/30 dark:!bg-amber-500/15',
                        }),
                        this.metric({
                            label: 'New Customers',
                            value: stats.total_customers?.current ?? 0,
                            change: stats.total_customers?.progress,
                            icon: "{{ bagisto_asset('images/customers.svg') }}",
                            iconBoxClass: '!border-emerald-200 !bg-emerald-50 dark:!border-emerald-500/30 dark:!bg-emerald-500/15',
                        }),
                        this.metric({
                            label: "@lang('admin::app.dashboard.index.total-unpaid-invoices')",
                            value: stats.total_unpaid_invoices?.formatted_total ?? '$0.00',
                            badgeText: 'Review',
                            icon: "{{ bagisto_asset('images/unpaid-invoices.svg') }}",
                            iconBoxClass: '!border-rose-200 !bg-rose-50 dark:!border-rose-500/30 dark:!bg-rose-500/15',
                            tone: 'rose',
                        }),
                        this.metric({
                            label: 'To Ship',
                            value: stats.to_ship?.current ?? 0,
                            badgeText: 'Queue',
                            iconClass: 'icon-ship',
                            iconBoxClass: '!border-sky-200 !bg-sky-50 dark:!border-sky-500/30 dark:!bg-sky-500/15',
                            iconTextClass: '!text-sky-600 dark:!text-sky-300',
                            tone: 'blue',
                        }),
                        this.metric({
                            label: 'In Delivery',
                            value: stats.in_delivery?.current ?? 0,
                            badgeText: 'Active',
                            icon: "{{ bagisto_asset('images/settings/shipping.svg') }}",
                            iconBoxClass: '!border-violet-200 !bg-violet-50 dark:!border-violet-500/30 dark:!bg-violet-500/15',
                            tone: 'amber',
                        }),
                        this.metric({
                            label: 'COD Receivable',
                            value: stats.cod_receivable?.formatted_total ?? '$0.00',
                            badgeText: 'Collect',
                            icon: "{{ bagisto_asset('images/settings/payment-method.svg') }}",
                            iconBoxClass: '!border-orange-200 !bg-orange-50 dark:!border-orange-500/30 dark:!bg-orange-500/15',
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
