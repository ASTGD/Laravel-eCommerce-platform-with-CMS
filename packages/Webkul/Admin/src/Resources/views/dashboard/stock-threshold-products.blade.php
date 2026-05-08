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
            <div class="overflow-hidden rounded-[1.25rem] bg-white p-6 shadow-sm shadow-slate-200/60 dark:bg-gray-800 dark:shadow-none" style="border-radius: 1.25rem;">
                <x-admin::shimmer.dashboard.stock-threshold-products />
            </div>
        </template>

        <template v-else>
            <article class="overflow-hidden rounded-[1.25rem] bg-white shadow-sm shadow-slate-200/60 dark:bg-gray-800 dark:shadow-none" style="border-radius: 1.25rem;">
                <div class="flex flex-col gap-4 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between dark:border-gray-700">
                    <div>
                        <h3 class="font-sans text-lg leading-7 font-semibold tracking-normal text-slate-950 dark:text-white">
                            Inventory Watchlist
                        </h3>
                    </div>

                    <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
                        Low Stock
                    </span>
                </div>

                <template v-if="report.statistics.length">
                    <div class="divide-y divide-slate-200 dark:divide-slate-800">
                        <a
                            v-for="product in report.statistics"
                            :href="'{{ route('admin.catalog.products.edit', ':replace') }}'.replace(':replace', product.id)"
                            class="flex items-center gap-3 px-6 py-5 transition hover:bg-slate-50/80 dark:hover:bg-slate-800/60"
                        >
                            <template v-if="product.image">
                                <img
                                    class="h-[64px] w-[64px] rounded-2xl object-cover shadow-sm"
                                    :src="product.image"
                                >
                            </template>

                            <template v-else>
                                <div class="relative flex h-[64px] w-[64px] items-center justify-center overflow-hidden rounded-2xl border border-dashed border-slate-300 bg-slate-50 dark:border-slate-700 dark:bg-gray-800">
                                    <img src="{{ bagisto_asset('images/product-placeholders/front.svg') }}" class="h-10 w-10">
                                </div>
                            </template>

                            <div class="min-w-0 flex-1">
                                <p class="text-base font-semibold text-slate-950 dark:text-white" v-text="product.name"></p>

                                <div class="mt-2 flex flex-wrap items-center gap-3 text-sm text-slate-500 dark:text-slate-400">
                                    <span>
                                        @{{ "@lang('admin::app.dashboard.index.sku', ['sku' => ':replace'])".replace(':replace', product.sku) }}
                                    </span>

                                    <span class="h-1 w-1 rounded-full bg-slate-300"></span>

                                    <span
                                        class="text-xs font-semibold leading-none whitespace-nowrap"
                                        :class="[product.total_qty > {{ core()->getConfigData('catalog.inventory.stock_options.out_of_stock_threshold') }} ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300']"
                                    >
                                        @{{ "@lang('admin::app.dashboard.index.total-stock', ['total_stock' => ':replace'])".replace(':replace', product.total_qty) }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center text-slate-400">
                                <span class="icon-sort-right rtl:icon-sort-left text-2xl"></span>
                            </div>
                        </a>
                    </div>
                </template>

                <template v-else>
                    <div class="grid justify-center justify-items-center gap-3 px-6 py-16 text-center">
                        <img src="{{ bagisto_asset('images/icon-add-product.svg') }}" class="h-20 w-20 dark:mix-blend-exclusion dark:invert">

                        <div class="flex flex-col items-center">
                        <p class="text-base font-semibold text-slate-500 dark:text-slate-300">
                            No low-stock items yet
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
