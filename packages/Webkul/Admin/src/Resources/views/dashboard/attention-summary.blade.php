<!-- Attention Summary Vue Component -->
<v-dashboard-attention-summary>
    <x-admin::shimmer.dashboard.todays-details />
</v-dashboard-attention-summary>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-dashboard-attention-summary-template"
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
                        Needs Action
                    </h3>
                </div>

                <div class="grid gap-3 border-b border-slate-200 px-6 py-5 dark:border-gray-700">
                    <a
                        href="{{ route('admin.sales.invoices.index') }}"
                        class="rounded-[1.125rem] bg-rose-50 px-4 py-4 transition hover:bg-rose-100/70 dark:bg-rose-500/10 dark:hover:bg-rose-500/15"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <span class="font-sans text-lg leading-7 font-semibold tracking-normal text-slate-950 dark:text-white">
                                    Unpaid Invoices
                                </span>

                                <p class="mt-2 font-sans text-2xl leading-8 font-bold tracking-tight text-rose-950 dark:text-rose-100">
                                    @{{ unpaid.formatted_total }}
                                </p>
                            </div>

                            <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-rose-700 shadow-sm dark:bg-rose-500/15 dark:text-rose-200">
                                @{{ unpaid.count }} open
                            </span>
                        </div>
                    </a>

                    <div class="grid gap-3 sm:grid-cols-3 xl:grid-cols-1 2xl:grid-cols-3">
                        <a
                        v-for="item in exceptionCards"
                        :key="item.label"
                        :href="item.url"
                        class="rounded-[1.125rem] bg-slate-50 px-4 py-3 transition hover:bg-slate-100/80 dark:bg-gray-800 dark:hover:bg-slate-800"
                    >
                            <p class="font-sans text-sm leading-5 font-medium tracking-normal text-slate-600 dark:text-slate-300">
                                @{{ item.label }}
                            </p>

                            <p class="mt-2 text-lg font-semibold text-slate-950 dark:text-white">
                                @{{ item.value }}
                            </p>
                        </a>
                    </div>
                </div>

                <div
                    v-if="unpaid.recent?.length"
                    class="divide-y divide-slate-200 dark:divide-slate-800"
                >
                    <a
                        v-for="invoice in unpaid.recent"
                        :key="invoice.id"
                        :href="'{{ route('admin.sales.invoices.view', ':id') }}'.replace(':id', invoice.id)"
                        class="flex items-center justify-between gap-4 px-6 py-4 transition hover:bg-slate-50/80 dark:hover:bg-slate-800/60"
                    >
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-950 dark:text-white">
                                Invoice #@{{ invoice.id }}
                            </p>

                            <p class="mt-1 truncate text-sm text-slate-500 dark:text-slate-400">
                                Order #@{{ invoice.order_increment_id }} · @{{ invoice.created_at }}
                            </p>
                        </div>

                        <p class="shrink-0 font-semibold text-slate-950 dark:text-white">
                            @{{ invoice.formatted_grand_total }}
                        </p>
                    </a>
                </div>

                <div
                    v-else
                    class="px-6 py-8 text-sm text-slate-500 dark:text-slate-400"
                >
                    No pending invoices are currently waiting for attention.
                </div>
            </article>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-attention-summary', {
            template: '#v-dashboard-attention-summary-template',

            data() {
                return {
                    report: {
                        statistics: {
                            unpaid_invoices: {
                                recent: [],
                            },
                            shipment_attention: {},
                        },
                    },

                    isLoading: true,
                }
            },

            computed: {
                unpaid() {
                    return this.report.statistics?.unpaid_invoices ?? {
                        count: 0,
                        formatted_total: '$0.00',
                        recent: [],
                    };
                },

                exceptionCards() {
                    const attention = this.report.statistics?.shipment_attention ?? {};

                    return [
                        {
                            label: 'Failed',
                            value: attention.delivery_failed?.count ?? 0,
                            url: "{{ route('admin.sales.shipment-operations.index') }}",
                        },
                        {
                            label: 'Reattempt',
                            value: attention.requires_reattempt?.count ?? 0,
                            url: "{{ route('admin.sales.shipment-operations.index') }}",
                        },
                        {
                            label: 'COD Exceptions',
                            value: attention.cod_exceptions?.formatted_amount ?? '$0.00',
                            url: "{{ route('admin.sales.cod-settlements.index') }}",
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

                    filters.type = 'attention';

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
