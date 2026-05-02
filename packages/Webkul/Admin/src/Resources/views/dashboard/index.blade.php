<x-admin::layouts>
    @php
        $adminName = auth()->guard('admin')->user()?->name ?? 'Admin';
    @endphp

    <x-slot:title>
        @lang('admin::app.dashboard.index.title')
    </x-slot>

    <div class="space-y-6 pb-8">
        <section class="rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm shadow-slate-200/60 lg:px-8 lg:py-7 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
            <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(0,1fr)] xl:items-center">
                <div class="space-y-4">
                    <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-semibold uppercase tracking-[0.28em] text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                        Admin dashboard
                    </div>

                    <div class="space-y-3">
                        <h1 class="text-3xl font-semibold tracking-tight text-slate-950 md:text-4xl dark:text-white">
                            @lang('admin::app.dashboard.index.user-name', ['user_name' => $adminName])
                        </h1>

                        <p class="max-w-2xl text-sm leading-6 text-slate-500 md:text-base dark:text-slate-400">
                            @lang('admin::app.dashboard.index.user-info')
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <a
                            href="#dashboard-overview"
                            class="inline-flex items-center gap-2 rounded-full bg-slate-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-100"
                        >
                            Overview
                        </a>

                        <a
                            href="#dashboard-activity"
                            class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800"
                        >
                            Activity
                        </a>
                    </div>
                </div>

                <article class="rounded-2xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-950">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500 dark:text-slate-400">
                            Dashboard filters
                        </p>
                    </div>

                    <div class="mt-3">
                        <v-dashboard-filters></v-dashboard-filters>
                    </div>
                </article>
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
                :class="channels.length > 2 ? 'xl:grid-cols-3' : 'xl:grid-cols-2'"
            >
                <template v-if="channels.length > 2">
                    <x-admin::dropdown position="bottom-right">
                        <x-slot:toggle>
                            <button
                                type="button"
                                class="inline-flex w-full cursor-pointer appearance-none items-center justify-between gap-x-2 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm leading-6 text-slate-900 transition hover:border-slate-300 hover:bg-slate-50 focus:border-slate-400 focus:outline-none dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:hover:border-slate-600 dark:hover:bg-slate-900 dark:focus:border-slate-500"
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
                        class="flex min-h-[44px] w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 transition hover:border-slate-300 focus:border-slate-400 focus:outline-none dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:placeholder:text-slate-500 dark:hover:border-slate-600 dark:focus:border-slate-500"
                        v-model="filters.start"
                        placeholder="@lang('admin::app.dashboard.index.start-date')"
                    />
                </x-admin::flat-picker.date>

                <x-admin::flat-picker.date class="!w-full">
                    <input
                        class="flex min-h-[44px] w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 transition hover:border-slate-300 focus:border-slate-400 focus:outline-none dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:placeholder:text-slate-500 dark:hover:border-slate-600 dark:focus:border-slate-500"
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
