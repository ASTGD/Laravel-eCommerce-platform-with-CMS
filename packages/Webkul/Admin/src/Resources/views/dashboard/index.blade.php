<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.dashboard.index.title')
    </x-slot>

    <div class="space-y-6 pb-8">
        <section class="border-b border-slate-200 pb-6 dark:border-slate-800">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
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

                <div class="w-full lg:max-w-[760px]">
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
            <div
                class="grid gap-2 sm:grid-cols-2"
                :class="channels.length > 2 ? 'lg:grid-cols-3' : 'lg:grid-cols-2'"
            >
                <template v-if="channels.length > 2">
                    <x-admin::dropdown position="bottom-right">
                        <x-slot:toggle>
                            <button
                                type="button"
                                class="inline-flex w-full cursor-pointer appearance-none items-center justify-between gap-x-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm leading-6 text-slate-900 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 focus:border-slate-400 focus:outline-none dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:hover:border-slate-600 dark:hover:bg-slate-900 dark:focus:border-slate-500"
                            >
                                @{{ channels.find(channel => channel.code == filters.channel).name }}

                                <span class="text-2xl text-slate-500 dark:text-slate-400 icon-sort-down"></span>
                            </button>
                        </x-slot>

                        <x-slot:menu class="!p-0 shadow-[0_5px_20px_rgba(0,0,0,0.15)] dark:border-gray-800">
                            <x-admin::dropdown.menu.item
                                v-for="channel in channels"
                                ::class="{'bg-gray-100 dark:bg-gray-950': channel.code == filters.channel}"
                                @click="filters.channel = channel.code"
                            >
                                @{{ channel.name }}
                            </x-admin::dropdown.menu.item>
                        </x-slot>
                    </x-admin::dropdown>
                </template>

                <x-admin::flat-picker.date class="!w-full">
                    <input
                        class="flex min-h-[42px] w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 shadow-sm transition hover:border-slate-300 focus:border-slate-400 focus:outline-none dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:placeholder:text-slate-500 dark:hover:border-slate-600 dark:focus:border-slate-500"
                        v-model="filters.start"
                        placeholder="@lang('admin::app.dashboard.index.start-date')"
                    />
                </x-admin::flat-picker.date>

                <x-admin::flat-picker.date class="!w-full">
                    <input
                        class="flex min-h-[42px] w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 shadow-sm transition hover:border-slate-300 focus:border-slate-400 focus:outline-none dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:placeholder:text-slate-500 dark:hover:border-slate-600 dark:focus:border-slate-500"
                        v-model="filters.end"
                        placeholder="@lang('admin::app.dashboard.index.end-date')"
                    />
                </x-admin::flat-picker.date>
            </div>
        </script>

        <script type="module">
            app.component('v-dashboard-filters', {
                template: '#v-dashboard-filters-template',

                data() {
                    return {
                        channels: [
                            {
                                name: "@lang('admin::app.dashboard.index.all-channels')",
                                code: ''
                            },
                            ...@json(core()->getAllChannels()),
                        ],

                        filters: {
                            channel: '',

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
