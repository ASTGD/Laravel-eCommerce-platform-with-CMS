<x-admin::layouts>
    @php
        $adminName = auth()->guard('admin')->user()?->name ?? 'Admin';
    @endphp

    <x-slot:title>
        @lang('admin::app.dashboard.index.title')
    </x-slot>

    <div class="space-y-6 pb-8">
        <section class="relative overflow-hidden rounded-[2rem] border border-slate-200 bg-slate-950 px-6 py-6 text-white shadow-2xl shadow-slate-950/20 lg:px-8 lg:py-8 dark:border-slate-800">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(251,146,60,0.22),_transparent_42%),radial-gradient(circle_at_bottom_left,_rgba(59,130,246,0.16),_transparent_35%)]"></div>

            <div class="relative grid gap-6 xl:grid-cols-[minmax(0,1.45fr)_minmax(320px,0.9fr)] xl:items-end">
                <div class="space-y-4">
                    <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-4 py-2 text-xs font-semibold uppercase tracking-[0.28em] text-slate-200">
                        Admin dashboard
                    </div>

                    <div class="space-y-3">
                        <h1 class="text-3xl font-semibold tracking-tight text-white md:text-4xl">
                            @lang('admin::app.dashboard.index.user-name', ['user_name' => $adminName])
                        </h1>

                        <p class="max-w-2xl text-sm leading-6 text-slate-300 md:text-base">
                            @lang('admin::app.dashboard.index.user-info')
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <a
                            href="#dashboard-overview"
                            class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-slate-100"
                        >
                            Overview
                        </a>

                        <a
                            href="#dashboard-activity"
                            class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/10"
                        >
                            Activity
                        </a>
                    </div>
                </div>

                <div class="grid gap-3">
                    <article class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-300">
                            Reporting window
                        </p>
                        <p class="mt-2 text-lg font-semibold text-white">
                            {{ $startDate->format('d M Y') }} - {{ $endDate->format('d M Y') }}
                        </p>
                        <p class="mt-1 text-sm text-slate-300">
                            Use the filters below to refresh the reporting scope.
                        </p>
                    </article>

                    <article class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-300">
                            Dashboard filters
                        </p>

                        <div class="mt-3">
                            <v-dashboard-filters></v-dashboard-filters>
                        </div>
                    </article>
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
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <template v-if="channels.length > 2">
                    <x-admin::dropdown position="bottom-right">
                        <x-slot:toggle>
                            <button
                                type="button"
                                class="inline-flex w-full cursor-pointer appearance-none items-center justify-between gap-x-2 rounded-2xl border border-white/10 bg-white/10 px-3 py-3 text-sm leading-6 text-white transition hover:border-white/20 hover:bg-white/15 focus:border-white/20"
                            >
                                @{{ channels.find(channel => channel.code == filters.channel).name }}

                                <span class="text-2xl icon-sort-down"></span>
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
                        class="flex min-h-[48px] w-full rounded-2xl border border-white/10 bg-white/10 px-3 py-3 text-sm text-white placeholder:text-slate-300 transition hover:border-white/20 focus:border-white/20 focus:outline-none"
                        v-model="filters.start"
                        placeholder="@lang('admin::app.dashboard.index.start-date')"
                    />
                </x-admin::flat-picker.date>

                <x-admin::flat-picker.date class="!w-full">
                    <input
                        class="flex min-h-[48px] w-full rounded-2xl border border-white/10 bg-white/10 px-3 py-3 text-sm text-white placeholder:text-slate-300 transition hover:border-white/20 focus:border-white/20 focus:outline-none"
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
