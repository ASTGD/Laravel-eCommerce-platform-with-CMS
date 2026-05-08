<!-- Top Selling Products Vue Component -->
<v-dashboard-top-customers>
    <!-- Shimmer -->
    <x-admin::shimmer.dashboard.top-customers />
</v-dashboard-top-customers>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-dashboard-top-customers-template"
    >
        <template v-if="isLoading">
            <div class="overflow-hidden rounded-[1.25rem] bg-white p-6 shadow-sm shadow-slate-200/60 dark:bg-gray-800 dark:shadow-none" style="border-radius: 1.25rem;">
                <x-admin::shimmer.dashboard.top-customers />
            </div>
        </template>

        <template v-else>
            <article class="overflow-hidden rounded-[1.25rem] bg-white shadow-sm shadow-slate-200/60 dark:bg-gray-800 dark:shadow-none" style="border-radius: 1.25rem;">
                <div class="flex flex-col gap-4 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between dark:border-gray-700">
                    <div>
                        <h3 class="font-sans text-lg leading-7 font-semibold tracking-normal text-slate-950 dark:text-white">
                            Top Customers
                        </h3>
                    </div>

                    <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                        @{{ report.date_range }}
                    </span>
                </div>

                <div v-if="report.statistics.length" class="divide-y divide-slate-200 dark:divide-slate-800">
                    <a
                        :href="customer.id ? '{{ route('admin.customers.customers.view', ':id') }}'.replace(':id', customer.id) : '#'"
                        class="flex items-start justify-between gap-4 px-6 py-5 transition hover:bg-slate-50/80 dark:hover:bg-slate-800/60"
                        v-for="customer in report.statistics"
                    >
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-950 dark:text-white">
                                @{{ customer.full_name }}
                            </p>

                            <p class="mt-1 truncate text-sm text-slate-500 dark:text-slate-400">
                                @{{ customer.email }}
                            </p>
                        </div>

                        <div class="text-right">
                            <p class="font-semibold text-slate-950 dark:text-white">
                                @{{ customer.formatted_total }}
                            </p>

                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400" v-if="customer.orders">
                                @{{ "@lang('admin::app.dashboard.index.order-count', ['count' => ':count'])".replace(':count', customer.orders) }}
                            </p>
                        </div>
                    </a>
                </div>

                <div v-else class="grid justify-center justify-items-center gap-3 px-6 py-16 text-center">
                    <img
                        src="{{ bagisto_asset('images/empty-placeholders/customers.svg') }}"
                        class="h-20 w-20 dark:mix-blend-exclusion dark:invert"
                    />

                    <div class="flex flex-col items-center">
                        <p class="text-base font-semibold text-slate-500 dark:text-slate-300">
                            No top customers yet
                        </p>
                    </div>
                </div>
            </article>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-top-customers', {
            template: '#v-dashboard-top-customers-template',

            data() {
                return {
                    report: [],

                    isLoading: true,
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

                    filters.type = 'top-customers';

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
