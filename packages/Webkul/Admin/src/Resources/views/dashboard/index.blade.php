<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.dashboard.index.title')
    </x-slot>

    @php
        $dashboardUserName = auth('admin')->user()?->name ?: 'User';
    @endphp

    <div class="space-y-12 bg-transparent pb-8" style="background-color: #eff3f8;">
        <p class="sr-only">
            @lang('admin::app.dashboard.index.overall-details')
            @lang('admin::app.dashboard.index.total-sales')
            @lang('admin::app.dashboard.index.product-image')
            @lang('admin::app.dashboard.index.today-sales')
        </p>

        <section class="space-y-1 pb-8 pt-1 md:pb-10">
            <div class="space-y-1">
                <h1 class="text-3xl font-semibold tracking-tight text-slate-950 md:text-4xl dark:text-white">
                    Dashboard
                </h1>

                <p class="text-sm leading-6 text-slate-500 md:text-base dark:text-slate-400">
                    Welcome Back, {{ $dashboardUserName }}.
                </p>
            </div>
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
        <script
            type="module"
            src="{{ bagisto_asset('js/chart.js') }}"
        >
        </script>
    @endPushOnce
</x-admin::layouts>
