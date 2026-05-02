<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.dashboard.index.title')
    </x-slot>

    <div class="space-y-6 pb-8">
        <section class="border-b border-slate-200 pb-6 dark:border-slate-800">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="space-y-1">
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-500 dark:text-slate-400">
                        Dashboard
                    </p>

                    <h1 class="text-3xl font-semibold tracking-tight text-slate-950 md:text-4xl dark:text-white">
                        Dashboard
                    </h1>

                    <p class="text-sm leading-6 text-slate-500 md:text-base dark:text-slate-400">
                        Welcome back
                    </p>
                </div>

                <div class="w-full lg:ml-auto lg:max-w-[560px]">
                    <v-dashboard-filters></v-dashboard-filters>
                </div>
            </div>
        </section>

        <section id="dashboard-overview" class="space-y-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">
                        Overview
                    </p>

                    <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950 dark:text-white">
                        Store performance
                    </h2>
                </div>

                <p class="max-w-2xl text-sm leading-6 text-slate-500 dark:text-slate-400">
                    Live summary cards powered by the current reporting filter.
                </p>
            </div>

            @include('admin::dashboard.over-all-details')
        </section>

        <section id="dashboard-activity" class="grid gap-6 xl:grid-cols-[minmax(0,1.55fr)_minmax(320px,0.85fr)]">
            <div class="space-y-6">
                @include('admin::dashboard.total-sales')
                @include('admin::dashboard.todays-details')
            </div>

            <div class="space-y-6">
                @include('admin::dashboard.stock-threshold-products')
                @include('admin::dashboard.top-selling-products')
                @include('admin::dashboard.top-customers')
            </div>
        </section>
    </div>

    @pushOnce('scripts')
        <script
            type="module"
            src="{{ bagisto_asset('js/chart.js') }}"
        >
        </script>

        <script
            type="text/x-template"
            id="v-dashboard-filters-template"
        >
            <div class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm dark:border-slate-800 dark:bg-slate-950 dark:shadow-none sm:grid-cols-2">
                <div class="space-y-2">
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">
                        Start Date
                    </p>

                    <x-admin::flat-picker.date class="!w-full">
                        <input
                            class="flex min-h-[42px] w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 shadow-sm transition hover:border-slate-300 focus:border-slate-400 focus:outline-none dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:placeholder:text-slate-500 dark:hover:border-slate-600 dark:focus:border-slate-500"
                            v-model="filters.start"
                            placeholder="@lang('admin::app.dashboard.index.start-date')"
                        />
                    </x-admin::flat-picker.date>
                </div>

                <div class="space-y-2">
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">
                        End Date
                    </p>

                    <x-admin::flat-picker.date class="!w-full">
                        <input
                            class="flex min-h-[42px] w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 shadow-sm transition hover:border-slate-300 focus:border-slate-400 focus:outline-none dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:placeholder:text-slate-500 dark:hover:border-slate-600 dark:focus:border-slate-500"
                            v-model="filters.end"
                            placeholder="@lang('admin::app.dashboard.index.end-date')"
                        />
                    </x-admin::flat-picker.date>
                </div>
            </div>
        </script>

        <script type="module">
            app.component('v-dashboard-filters', {
                template: '#v-dashboard-filters-template',

                data() {
                    return {
                        filters: {
                            start: "{{ $startDate->format('Y-m-d') }}",

                            end: "{{ $endDate->format('Y-m-d') }}",
                        }
                    }
                },

                watch: {
                    filters: {
                        handler() {
                            this.$emitter.emit('reporting-filter-updated', this.filters);
                        },

                        deep: true
                    }
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts>
