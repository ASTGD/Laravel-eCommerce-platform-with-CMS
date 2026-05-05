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
                    <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5 dark:border-slate-800">
                        <h3 class="font-sans text-lg leading-7 font-semibold tracking-normal text-slate-950 dark:text-white">
                            Orders Moving
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
                    <div class="flex items-center justify-between border-b border-slate-200 px-6 py-5 dark:border-slate-800">
                        <h3 class="font-sans text-lg leading-7 font-semibold tracking-normal text-slate-950 dark:text-white">
                            COD Collection
                        </h3>
                    </div>

                    <div class="px-6 py-5">
                        <p class="font-sans text-sm leading-5 font-medium tracking-normal text-slate-600 dark:text-slate-300">
                            COD Receivable
                        </p>

                        <div class="mt-2 flex flex-wrap items-end justify-between gap-3">
                            <p class="text-3xl font-semibold tracking-tight text-slate-950 dark:text-white">
                                @{{ cod.receivable?.formatted_amount ?? '$0.00' }}
                            </p>

                            <span class="text-sm font-medium text-slate-500 dark:text-slate-400">
                                @{{ cod.receivable?.count ?? 0 }} eligible orders
                            </span>
                        </div>
                    </div>

                    <div class="grid gap-3 border-t border-slate-200 px-6 py-5 sm:grid-cols-2 dark:border-slate-800">
                        <a
                            v-for="item in codCards"
                            :key="item.label"
                            :href="item.url"
                            class="rounded-[1rem] bg-slate-50 px-4 py-3 transition hover:bg-slate-100/80 dark:bg-slate-950 dark:hover:bg-slate-800"
                        >
                            <p class="font-sans text-sm leading-5 font-medium tracking-normal text-slate-600 dark:text-slate-300">
                                @{{ item.label }}
                            </p>

                            <p class="mt-2 text-xl font-semibold tracking-tight text-slate-950 dark:text-white">
                                @{{ item.value }}
                            </p>
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
                cod() {
                    return this.report.statistics?.cod ?? {};
                },

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
                            label: 'Active COD Orders',
                            value: cod.active_orders?.count ?? 0,
                            url: "{{ route('admin.sales.orders.index') }}",
                        },
                        {
                            label: 'Shipped COD',
                            value: cod.shipped_orders?.count ?? 0,
                            url: "{{ route('admin.sales.orders.index') }}",
                        },
                        {
                            label: 'Completed COD',
                            value: cod.completed_orders?.count ?? 0,
                            url: "{{ route('admin.sales.orders.index') }}",
                        },
                        {
                            label: 'COD Exceptions',
                            value: cod.exceptions?.count ?? 0,
                            url: "{{ route('admin.sales.orders.index') }}",
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
