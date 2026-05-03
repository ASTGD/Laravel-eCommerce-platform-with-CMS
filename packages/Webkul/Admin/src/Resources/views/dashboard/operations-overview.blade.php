<!-- Operations Overview Vue Component -->
<v-dashboard-operations-overview>
    <div class="grid gap-6 md:grid-cols-2">
        <x-admin::shimmer.dashboard.todays-details />
        <x-admin::shimmer.dashboard.todays-details />
    </div>
</v-dashboard-operations-overview>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-dashboard-operations-overview-template"
    >
        <template v-if="isLoading">
            <div class="grid gap-6 md:grid-cols-2">
                <div class="overflow-hidden rounded-[1.25rem] bg-white p-6 shadow-sm shadow-slate-200/60 dark:bg-slate-900 dark:shadow-none" style="border-radius: 1.25rem;">
                    <x-admin::shimmer.dashboard.todays-details />
                </div>

                <div class="overflow-hidden rounded-[1.25rem] bg-white p-6 shadow-sm shadow-slate-200/60 dark:bg-slate-900 dark:shadow-none" style="border-radius: 1.25rem;">
                    <x-admin::shimmer.dashboard.todays-details />
                </div>
            </div>
        </template>

        <template v-else>
            <div class="grid h-full gap-6 md:grid-cols-2">
                <article class="overflow-hidden rounded-[1.25rem] bg-white shadow-sm shadow-slate-200/60 dark:bg-slate-900 dark:shadow-none" style="border-radius: 1.25rem;">
                    <div class="border-b border-slate-200 px-6 py-5 dark:border-slate-800">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">
                            Shipment Overview
                        </p>

                        <h3 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950 dark:text-white">
                            Orders moving
                        </h3>
                    </div>

                    <div class="divide-y divide-slate-200 dark:divide-slate-800">
                        <a
                            v-for="item in shipmentCards"
                            :key="item.label"
                            :href="item.url"
                            class="flex items-center justify-between gap-4 px-6 py-4 transition hover:bg-slate-50/80 dark:hover:bg-slate-800/60"
                        >
                            <div>
                                <p class="font-semibold text-slate-950 dark:text-white">
                                    @{{ item.label }}
                                </p>
                            </div>

                            <span
                                class="inline-flex min-w-[56px] justify-center rounded-full px-3 py-1.5 text-sm font-semibold"
                                :class="item.class"
                            >
                                @{{ item.value }}
                            </span>
                        </a>
                    </div>
                </article>

                <article class="overflow-hidden rounded-[1.25rem] bg-white shadow-sm shadow-slate-200/60 dark:bg-slate-900 dark:shadow-none" style="border-radius: 1.25rem;">
                    <div class="border-b border-slate-200 px-6 py-5 dark:border-slate-800">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">
                            COD Overview
                        </p>

                        <h3 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950 dark:text-white">
                            Cash collection
                        </h3>
                    </div>

                    <div class="divide-y divide-slate-200 dark:divide-slate-800">
                        <a
                            v-for="item in codCards"
                            :key="item.label"
                            :href="item.url"
                            class="flex items-center justify-between gap-4 px-6 py-4 transition hover:bg-slate-50/80 dark:hover:bg-slate-800/60"
                        >
                            <div>
                                <p class="font-semibold text-slate-950 dark:text-white">
                                    @{{ item.label }}
                                </p>
                            </div>

                            <span
                                class="inline-flex min-w-[96px] justify-center rounded-full px-3 py-1.5 text-sm font-semibold"
                                :class="item.class"
                            >
                                @{{ item.value }}
                            </span>
                        </a>
                    </div>
                </article>
            </div>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-operations-overview', {
            template: '#v-dashboard-operations-overview-template',

            data() {
                return {
                    report: {
                        statistics: {
                            shipment: {},
                            cod: {},
                        },
                    },

                    isLoading: true,
                }
            },

            computed: {
                shipmentCards() {
                    const shipment = this.report.statistics?.shipment ?? {};

                    return [
                        {
                            label: 'To Ship',
                            value: shipment.to_ship?.count ?? 0,
                            url: "{{ route('admin.sales.to-ship.index') }}",
                            class: 'bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
                        },
                        {
                            label: 'Ready for Handover',
                            value: shipment.ready_for_handover?.count ?? 0,
                            url: "{{ route('admin.sales.to-ship.index') }}#parcel-ready-for-handover",
                            class: 'bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300',
                        },
                        {
                            label: 'In Delivery',
                            value: shipment.in_delivery?.count ?? 0,
                            url: "{{ route('admin.sales.shipped-orders.index') }}",
                            class: 'bg-cyan-50 text-cyan-700 dark:bg-cyan-500/15 dark:text-cyan-300',
                        },
                        {
                            label: 'Delivered Today',
                            value: shipment.delivered_today?.count ?? 0,
                            url: "{{ route('admin.sales.shipment-operations.index') }}",
                            class: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
                        },
                    ];
                },

                codCards() {
                    const cod = this.report.statistics?.cod ?? {};

                    return [
                        {
                            label: 'COD Receivable',
                            value: cod.receivable?.formatted_amount ?? '$0.00',
                            url: "{{ route('admin.sales.cod-receivables.index') }}",
                            class: 'bg-orange-50 text-orange-700 dark:bg-orange-500/15 dark:text-orange-300',
                        },
                        {
                            label: 'Received Today',
                            value: cod.received_today?.formatted_amount ?? '$0.00',
                            url: "{{ route('admin.sales.cod-receivables.index') }}",
                            class: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
                        },
                        {
                            label: 'Pending Remittance',
                            value: cod.pending_courier_remittance?.formatted_amount ?? '$0.00',
                            url: "{{ route('admin.sales.cod-settlements.index') }}",
                            class: 'bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
                        },
                        {
                            label: 'Partially Settled',
                            value: cod.partially_settled?.formatted_amount ?? '$0.00',
                            url: "{{ route('admin.sales.cod-settlements.index') }}",
                            class: 'bg-rose-50 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300',
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

                    filters.type = 'operations-overview';

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
