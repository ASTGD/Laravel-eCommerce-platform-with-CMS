<!-- Top Selling Products Vue Component -->
<v-dashboard-top-selling-products>
    <!-- Shimmer -->
    <x-admin::shimmer.dashboard.top-selling-products />
</v-dashboard-top-selling-products>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-dashboard-top-selling-products-template"
    >
        <template v-if="isLoading">
            <div class="overflow-hidden rounded-[1.25rem] bg-white p-6 shadow-sm shadow-slate-200/60 dark:bg-slate-900 dark:shadow-none" style="border-radius: 1.25rem;">
                <x-admin::shimmer.dashboard.top-selling-products />
            </div>
        </template>

        <template v-else>
            <article class="overflow-hidden rounded-[1.25rem] bg-white shadow-sm shadow-slate-200/60 dark:bg-slate-900 dark:shadow-none" style="border-radius: 1.25rem;">
                <div class="flex flex-col gap-4 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800">
                    <div>
                        <h3 class="font-sans text-lg leading-7 font-semibold tracking-normal text-slate-950 dark:text-white">
                            Top Products
                        </h3>
                    </div>

                    <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                        @{{ report.date_range }}
                    </span>
                </div>

                <div v-if="report.statistics.length" class="divide-y divide-slate-200 dark:divide-slate-800">
                    <a
                        :href="'{{ route('admin.catalog.products.edit', ':id') }}'.replace(':id', item.id)"
                        class="flex gap-3 px-6 py-5 transition hover:bg-slate-50/80 dark:hover:bg-slate-800/60"
                        v-for="item in report.statistics"
                    >
                        <template v-if="item.images?.length">
                            <img
                                class="h-[64px] w-[64px] rounded-2xl object-cover shadow-sm"
                                :src="item.images[0]?.url"
                            />
                        </template>

                        <template v-else>
                            <div class="relative flex h-[64px] w-[64px] items-center justify-center overflow-hidden rounded-2xl border border-dashed border-slate-300 bg-slate-50 dark:border-slate-700 dark:bg-slate-950">
                                <img src="{{ bagisto_asset('images/product-placeholders/front.svg')}}" class="h-10 w-10">
                            </div>
                        </template>

                        <div class="min-w-0 flex-1">
                            <p class="text-base font-semibold text-slate-950 dark:text-white" v-text="item.name"></p>

                            <div class="mt-2 flex flex-wrap items-center gap-3 text-sm text-slate-500 dark:text-slate-400">
                                <span>@{{ item.formatted_price }}</span>
                                <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                                <span>@{{ item.formatted_revenue }}</span>
                            </div>
                        </div>

                        <div class="flex items-center text-slate-400">
                            <span class="icon-sort-right rtl:icon-sort-left text-2xl"></span>
                        </div>
                    </a>
                </div>

                <div v-else class="grid justify-center justify-items-center gap-3 px-6 py-16 text-center">
                        <img
                            src="{{ bagisto_asset('images/icon-add-product.svg') }}"
                            class="h-20 w-20 dark:mix-blend-exclusion dark:invert"
                    >

                    <div class="flex flex-col items-center">
                        <p class="text-base font-semibold text-slate-500 dark:text-slate-300">
                            No top-selling products yet
                        </p>
                    </div>
                </div>
            </article>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-top-selling-products', {
            template: '#v-dashboard-top-selling-products-template',

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

                    filters.type = 'top-selling-products';

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
