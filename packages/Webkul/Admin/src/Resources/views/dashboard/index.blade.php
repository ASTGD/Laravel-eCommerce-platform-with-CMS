<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.dashboard.index.title')
    </x-slot>

    @php
        $dashboardUserName = auth('admin')->user()?->name ?: 'User';
        $baseDashboardQuery = request()->except(['range', 'from', 'to', 'start', 'end']);
        $customFromValue = $dashboardDateFilter['from'] ?? now()->subDays(30)->toDateString();
        $customToValue = $dashboardDateFilter['to'] ?? now()->toDateString();
    @endphp

    <div class="space-y-8 bg-transparent pb-8" style="background-color: #eff3f8;">
        <p class="sr-only">
            @lang('admin::app.dashboard.index.overall-details')
            @lang('admin::app.dashboard.index.total-sales')
            @lang('admin::app.dashboard.index.product-image')
            @lang('admin::app.dashboard.index.today-sales')
        </p>

        <section class="flex flex-col gap-5 pb-1 pt-1 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-1">
                <h1 class="text-3xl font-semibold tracking-tight text-slate-950 md:text-4xl dark:text-white">
                    Dashboard
                </h1>

                <p class="text-sm leading-6 text-slate-500 md:text-base dark:text-slate-400">
                    Welcome Back, {{ $dashboardUserName }}.
                </p>
            </div>

            <form
                method="GET"
                action="{{ route('admin.dashboard.index') }}"
                class="flex w-full flex-wrap items-center gap-2 lg:w-auto lg:justify-end"
            >
                @foreach ($baseDashboardQuery as $key => $value)
                    @if (is_scalar($value))
                        <input
                            type="hidden"
                            name="{{ $key }}"
                            value="{{ $value }}"
                        >
                    @endif
                @endforeach

                <input
                    type="hidden"
                    name="range"
                    value="custom"
                >

                <label class="sr-only" for="dashboard-filter-from">From</label>
                <input
                    id="dashboard-filter-from"
                    type="date"
                    name="from"
                    value="{{ $customFromValue }}"
                    class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600 outline-none transition focus:border-blue-300 focus:ring-2 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:focus:border-blue-500/50 dark:focus:ring-blue-500/10"
                >

                <span class="text-sm text-slate-400 dark:text-slate-500">to</span>

                <label class="sr-only" for="dashboard-filter-to">To</label>
                <input
                    id="dashboard-filter-to"
                    type="date"
                    name="to"
                    value="{{ $customToValue }}"
                    class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600 outline-none transition focus:border-blue-300 focus:ring-2 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:focus:border-blue-500/50 dark:focus:ring-blue-500/10"
                >

                <button
                    type="submit"
                    class="rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-600 transition hover:bg-blue-100 dark:border-blue-500/40 dark:bg-blue-500/10 dark:text-blue-300 dark:hover:bg-blue-500/15"
                >
                    Apply
                </button>
            </form>
        </section>

        <section id="dashboard-executive-kpis">
            @include('admin::dashboard.over-all-details')
        </section>

        <section id="dashboard-main-charts" class="mt-8 grid gap-8 xl:mt-12 xl:grid-cols-2">
            <div class="min-w-0">
                @include('admin::dashboard.total-sales')
            </div>

            <div class="min-w-0">
                @include('admin::dashboard.operations-trend')
            </div>
        </section>

        <section id="dashboard-operations" class="mt-6 grid gap-8 xl:mt-8 xl:grid-cols-[minmax(320px,0.95fr)_minmax(0,1.45fr)]">
            <div class="min-w-0">
                @include('admin::dashboard.todays-details')
            </div>

            <div class="min-w-0">
                @include('admin::dashboard.operations-overview')
            </div>
        </section>

        <section id="dashboard-commercial-intelligence" class="mt-6 grid gap-8 xl:mt-8 xl:grid-cols-2">
            <div class="min-w-0">
                @include('admin::dashboard.top-selling-products')
            </div>

            <div class="min-w-0">
                @include('admin::dashboard.top-customers')
            </div>
        </section>

        <section id="dashboard-attention" class="mt-6 grid gap-8 xl:mt-8 xl:grid-cols-[minmax(0,1.25fr)_minmax(320px,0.75fr)]">
            <div class="min-w-0">
                @include('admin::dashboard.stock-threshold-products')
            </div>

            <div class="min-w-0">
                @include('admin::dashboard.attention-summary')
            </div>
        </section>
    </div>

    @pushOnce('scripts')
        <script>
            window.dashboardStatsFilters = @json($dashboardDateFilter['query'] ?? []);
            window.dashboardStatsEndpoint = @json(route('admin.dashboard.stats'));

            function registerDashboardStatsFilterInterceptor() {
                if (! window.axios || window.dashboardStatsFilterInterceptorRegistered) {
                    return;
                }

                window.dashboardStatsFilterInterceptorRegistered = true;

                window.axios.interceptors.request.use((config) => {
                    const requestPath = new URL(config.url, window.location.origin).pathname;
                    const statsPath = new URL(window.dashboardStatsEndpoint, window.location.origin).pathname;

                    if (requestPath === statsPath) {
                        config.params = Object.assign({}, window.dashboardStatsFilters, config.params || {});
                    }

                    return config;
                });
            }

            registerDashboardStatsFilterInterceptor();
            window.addEventListener('load', registerDashboardStatsFilterInterceptor, { once: true });
        </script>

        <script
            type="module"
            src="{{ bagisto_asset('js/chart.js') }}"
        >
        </script>
    @endPushOnce
</x-admin::layouts>
