<!-- Stock Threshold Products Vue Component -->
<v-dashboard-stock-threshold-products>
    <!-- Shimmer -->
    <x-admin::shimmer.dashboard.stock-threshold-products />
</v-dashboard-stock-threshold-products>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-dashboard-stock-threshold-products-template"
    >
        <template v-if="isLoading">
            <div class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                <x-admin::shimmer.dashboard.stock-threshold-products />
            </div>
        </template>

        <template v-else>
            <article class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm shadow-slate-200/60 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                <div class="flex flex-col gap-4 border-b border-slate-200 px-6 py-6 sm:flex-row sm:items-end sm:justify-between dark:border-slate-800">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">
                            @lang('admin::app.dashboard.index.stock-threshold')
                        </p>

                        <h3 class="mt-2 text-xl font-semibold tracking-tight text-slate-950 dark:text-white">
                            Inventory watchlist
                        </h3>
                    </div>

                    <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
                        Low stock
                    </span>
                </div>

                <template v-if="report.statistics.length">
                    <div class="divide-y divide-slate-200 dark:divide-slate-800">
                        <div
                            v-for="product in report.statistics"
                            class="flex flex-col gap-4 px-6 py-5 transition hover:bg-slate-50/80 dark:hover:bg-slate-800/60 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div class="flex min-w-0 flex-1 items-start gap-3">
                                <template v-if="product.image">
                                    <img
                                        class="h-[64px] w-[64px] rounded-2xl object-cover shadow-sm"
                                        :src="product.image"
                                    >
                                </template>

                                <template v-else>
                                    <div class="relative flex h-[64px] w-[64px] items-center justify-center overflow-hidden rounded-2xl border border-dashed border-slate-300 bg-slate-50 dark:border-slate-700 dark:bg-slate-950">
                                        <img src="{{ bagisto_asset('images/product-placeholders/front.svg') }}" class="h-10 w-10">
                                    </div>
                                </template>

                                <div class="min-w-0">
                                    <p class="text-base font-semibold text-slate-950 dark:text-white">
                                        @{{ product.name }}
                                    </p>

                                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                        @{{ "@lang('admin::app.dashboard.index.sku', ['sku' => ':replace'])".replace(':replace', product.sku) }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex flex-nowrap items-center gap-2 sm:justify-end sm:whitespace-nowrap">
                                <div
                                    class="shrink-0 inline-flex min-w-[92px] items-center justify-center rounded-full border px-3 py-2 text-center"
                                    :class="[product.total_qty > {{ core()->getConfigData('catalog.inventory.stock_options.out_of_stock_threshold') }} ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300' : 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300']"
                                >
                                    <p class="text-sm font-semibold leading-none whitespace-nowrap">
                                        @{{ "@lang('admin::app.dashboard.index.total-stock', ['total_stock' => ':replace'])".replace(':replace', product.total_qty) }}
                                    </p>
                                </div>

                                <a
                                    :href="'{{ route('admin.catalog.products.edit', ':replace') }}'.replace(':replace', product.id)"
                                    class="shrink-0 inline-flex items-center text-sm font-semibold text-blue-600 transition hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                                >
                                    Open
                                </a>
                            </div>
                        </div>
                    </div>
                </template>

                <template v-else>
                    <div class="grid justify-center justify-items-center gap-3 px-6 py-16 text-center">
                        <img src="{{ bagisto_asset('images/icon-add-product.svg') }}" class="h-20 w-20 dark:mix-blend-exclusion dark:invert">

                        <div class="flex flex-col items-center">
                            <p class="text-base font-semibold text-slate-500 dark:text-slate-300">
                                No low-stock items yet
                            </p>

                            <p class="text-sm text-slate-400 dark:text-slate-400">
                                Inventory that falls under the threshold will appear here.
                            </p>
                        </div>
                    </div>
                </template>
            </article>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-stock-threshold-products', {
            template: '#v-dashboard-stock-threshold-products-template',

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

                    filters.type = 'stock-threshold-products';

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
