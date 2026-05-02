<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.dashboard.index.title')
    </x-slot>

    <div class="space-y-6 pb-8">
        <section class="border-b border-slate-200 pb-6 dark:border-slate-800">
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
                    Live summary cards for the current reporting period.
                </p>
            </div>

            @include('admin::dashboard.over-all-details')
        </section>

        <section id="dashboard-activity" class="grid gap-6 xl:grid-cols-[minmax(0,1.55fr)_minmax(320px,0.85fr)]">
            <div class="space-y-6">
                @include('admin::dashboard.total-sales')
                @include('admin::dashboard.todays-details')
                @include('admin::dashboard.stock-threshold-products')
            </div>

            <div class="space-y-6">
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
    @endPushOnce
</x-admin::layouts>
