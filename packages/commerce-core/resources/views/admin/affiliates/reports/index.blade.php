@php
    use Platform\CommerceCore\Models\AffiliateCommission;
    use Platform\CommerceCore\Models\AffiliatePayout;

    $formatMoney = fn ($amount): string => trim(view('commerce-core::admin.affiliates.partials.money', ['amount' => $amount])->render());
    $commissionTotals = $summary['commissions'] ?? [];
    $balance = $summary['balance'] ?? [];
    $chartLabels = collect($series['labels'] ?? [])->map(fn ($date): string => core()->formatDate($date, 'd M'))->all();
    $chartTooltipDates = collect($series['labels'] ?? [])->map(fn ($date): string => core()->formatDate($date, 'd M Y'))->all();
    $trafficTrendDatasets = [
        [
            'label' => 'Clicks',
            'data' => $series['clicks'] ?? [],
            'yAxisID' => 'count',
            'tension' => 0.3,
            'pointRadius' => 2.5,
            'pointHoverRadius' => 5,
            'borderWidth' => 2,
            'borderColor' => '#00A4EF',
            'pointBackgroundColor' => '#00A4EF',
            'pointBorderColor' => '#ffffff',
            'pointBorderWidth' => 1.5,
            'backgroundColor' => 'rgba(0, 164, 239, 0.14)',
            'fill' => true,
        ],
        [
            'label' => 'Orders',
            'data' => $series['orders'] ?? [],
            'yAxisID' => 'count',
            'tension' => 0.3,
            'pointRadius' => 2.5,
            'pointHoverRadius' => 5,
            'borderWidth' => 2,
            'borderColor' => '#7FBA00',
            'pointBackgroundColor' => '#7FBA00',
            'pointBorderColor' => '#ffffff',
            'pointBorderWidth' => 1.5,
            'backgroundColor' => 'rgba(127, 186, 0, 0.10)',
            'fill' => true,
        ],
        [
            'label' => 'Commission',
            'data' => $series['commissions'] ?? [],
            'yAxisID' => 'money',
            'tension' => 0.3,
            'pointRadius' => 2.5,
            'pointHoverRadius' => 5,
            'borderWidth' => 2,
            'borderColor' => '#FFB900',
            'pointBackgroundColor' => '#FFB900',
            'pointBorderColor' => '#ffffff',
            'pointBorderWidth' => 1.5,
            'backgroundColor' => 'rgba(255, 185, 0, 0.12)',
            'fill' => true,
        ],
    ];
    $payoutTrendDatasets = [
        [
            'label' => 'Paid Out',
            'data' => $series['paid_payouts'] ?? $series['payouts'] ?? [],
            'yAxisID' => 'money',
            'tension' => 0.3,
            'pointRadius' => 2.5,
            'pointHoverRadius' => 5,
            'borderWidth' => 2,
            'borderColor' => '#7FBA00',
            'pointBackgroundColor' => '#7FBA00',
            'pointBorderColor' => '#ffffff',
            'pointBorderWidth' => 1.5,
            'backgroundColor' => 'rgba(127, 186, 0, 0.12)',
            'fill' => true,
        ],
        [
            'label' => 'Reversed',
            'data' => $series['reversed_commissions'] ?? $series['reversed'] ?? [],
            'yAxisID' => 'money',
            'tension' => 0.3,
            'pointRadius' => 2.5,
            'pointHoverRadius' => 5,
            'borderWidth' => 2,
            'borderColor' => '#F25022',
            'pointBackgroundColor' => '#F25022',
            'pointBorderColor' => '#ffffff',
            'pointBorderWidth' => 1.5,
            'backgroundColor' => 'rgba(242, 80, 34, 0.12)',
            'fill' => true,
        ],
    ];
    $registrationTrendDatasets = [
        [
            'label' => 'Affiliate Registrations',
            'data' => $series['registrations'] ?? [],
            'borderWidth' => 1,
            'borderColor' => '#00A4EF',
            'backgroundColor' => 'rgba(0, 164, 239, 0.72)',
            'hoverBackgroundColor' => 'rgba(0, 164, 239, 0.86)',
            'borderRadius' => 8,
            'barPercentage' => 0.62,
            'categoryPercentage' => 0.72,
        ],
    ];
    $reportChartData = [
        'labels' => $chartLabels,
        'tooltipDates' => $chartTooltipDates,
        'trafficTrendDatasets' => $trafficTrendDatasets,
        'payoutTrendDatasets' => $payoutTrendDatasets,
        'registrationTrendDatasets' => $registrationTrendDatasets,
    ];

    $kpiCards = [
        [
            'label' => 'Total Clicks',
            'value' => number_format($kpis['total_clicks'] ?? 0),
            'helper' => 'Tracked referral visits',
            'color' => '#00A4EF',
            'badge' => 'bg-[#00A4EF]/12 text-[#007db7] ring-[#00A4EF]/28 dark:bg-[#00A4EF]/18 dark:text-[#8ddcff] dark:ring-[#00A4EF]/40',
            'icon' => 'icon-report',
        ],
        [
            'label' => 'Unique Visitors',
            'value' => number_format($kpis['unique_visitors'] ?? 0),
            'helper' => 'Distinct referral visitors',
            'color' => '#737373',
            'badge' => 'bg-[#737373]/10 text-[#5f5f5f] ring-[#737373]/24 dark:bg-[#737373]/20 dark:text-[#d4d4d4] dark:ring-[#737373]/40',
            'icon' => 'icon-customer-2',
        ],
        [
            'label' => 'Referred Orders',
            'value' => number_format($kpis['referred_orders'] ?? 0),
            'helper' => 'Orders attributed to affiliates',
            'color' => '#7FBA00',
            'badge' => 'bg-[#7FBA00]/12 text-[#5f8c00] ring-[#7FBA00]/28 dark:bg-[#7FBA00]/18 dark:text-[#b7e56a] dark:ring-[#7FBA00]/40',
            'icon' => 'icon-cart',
        ],
        [
            'label' => 'Conversion Rate',
            'value' => number_format($kpis['conversion_rate'] ?? 0, 2).'%',
            'helper' => 'Orders divided by clicks',
            'color' => '#7FBA00',
            'badge' => 'bg-[#7FBA00]/12 text-[#5f8c00] ring-[#7FBA00]/28 dark:bg-[#7FBA00]/18 dark:text-[#b7e56a] dark:ring-[#7FBA00]/40',
            'icon' => 'icon-up-stat',
        ],
        [
            'label' => 'Commission Earned',
            'value' => $formatMoney($kpis['total_commission_earned'] ?? 0),
            'helper' => 'Pending, approved, and paid',
            'color' => '#7FBA00',
            'badge' => 'bg-[#7FBA00]/12 text-[#5f8c00] ring-[#7FBA00]/28 dark:bg-[#7FBA00]/18 dark:text-[#b7e56a] dark:ring-[#7FBA00]/40',
            'icon' => 'icon-sales',
        ],
        [
            'label' => 'Available Balance',
            'value' => $formatMoney($kpis['available_balance'] ?? 0),
            'helper' => 'Payable after paid and reserved payouts',
            'color' => '#7FBA00',
            'badge' => 'bg-[#7FBA00]/12 text-[#5f8c00] ring-[#7FBA00]/28 dark:bg-[#7FBA00]/18 dark:text-[#b7e56a] dark:ring-[#7FBA00]/40',
            'icon' => 'icon-done',
        ],
        [
            'label' => 'Paid Out',
            'value' => $formatMoney($kpis['paid_out'] ?? 0),
            'helper' => 'Completed affiliate payouts',
            'color' => '#737373',
            'badge' => 'bg-[#737373]/10 text-[#5f5f5f] ring-[#737373]/24 dark:bg-[#737373]/20 dark:text-[#d4d4d4] dark:ring-[#737373]/40',
            'icon' => 'icon-admin-export',
        ],
        [
            'label' => 'Pending Requests',
            'value' => number_format($kpis['pending_payout_requests'] ?? 0),
            'helper' => 'Withdrawal requests needing review',
            'color' => '#FFB900',
            'badge' => 'bg-[#FFB900]/14 text-[#8a6400] ring-[#FFB900]/32 dark:bg-[#FFB900]/20 dark:text-[#ffd766] dark:ring-[#FFB900]/45',
            'icon' => 'icon-information',
        ],
    ];

    $statusPillClass = fn (string $status): string => match ($status) {
        AffiliatePayout::STATUS_REQUESTED => 'border-[#FFB900]/35 bg-[#FFB900]/10 text-[#8a6400] dark:border-[#FFB900]/45 dark:bg-[#FFB900]/15 dark:text-[#ffd766]',
        AffiliatePayout::STATUS_APPROVED => 'border-[#7FBA00]/25 bg-[#7FBA00]/10 text-[#4d7100] dark:border-[#7FBA00]/40 dark:bg-[#7FBA00]/15 dark:text-[#b7e56a]',
        AffiliatePayout::STATUS_PAID => 'border-[#00A4EF]/25 bg-[#00A4EF]/10 text-[#006fa1] dark:border-[#00A4EF]/40 dark:bg-[#00A4EF]/15 dark:text-[#8ddcff]',
        AffiliatePayout::STATUS_REJECTED => 'border-[#F25022]/25 bg-[#F25022]/10 text-[#a62e12] dark:border-[#F25022]/45 dark:bg-[#F25022]/15 dark:text-[#ffb19c]',
        default => 'border-[#737373]/25 bg-[#737373]/10 text-[#525252] dark:border-[#737373]/45 dark:bg-[#737373]/15 dark:text-[#d4d4d4]',
    };
@endphp

<x-admin::layouts>
    <x-slot:title>
        Affiliate Overview
    </x-slot>

    <div class="grid w-full min-w-0 gap-6">
        <section class="flex flex-col gap-4 pt-1 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight text-slate-950 md:text-4xl dark:text-white">
                    Affiliate Overview
                </h1>
            </div>

            <form
                method="GET"
                action="{{ route('admin.affiliates.overview.index') }}"
                class="flex w-full flex-wrap items-center gap-2 rounded-xl border border-slate-200 bg-white p-2 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:w-auto"
            >
                <label for="range" class="whitespace-nowrap text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                    Chart Range
                </label>

                <select
                    id="range"
                    name="range"
                    class="h-10 rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-800 shadow-sm outline-none transition focus:border-[#00A4EF] dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                    onchange="this.form.submit()"
                >
                    @foreach ($rangeOptions as $option)
                        <option value="{{ $option }}" @selected($rangeDays === $option)>
                            Last {{ $option }} days
                        </option>
                    @endforeach
                </select>
            </form>
        </section>

        <div class="affiliate-report-kpi-grid grid min-w-0 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($kpiCards as $card)
                <div
                    class="group relative min-h-[136px] min-w-0 overflow-hidden rounded-[24px] border border-slate-200/70 bg-white p-5 shadow-none transition-colors duration-200 hover:border-slate-300 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-gray-600"
                    style="--affiliate-report-color: {{ $card['color'] }};"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0 space-y-1.5 pr-16">
                            <h3 class="font-sans text-[13px] font-medium leading-5 tracking-normal text-slate-500 dark:text-slate-400">
                                {{ $card['label'] }}
                            </h3>

                            <div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
                                <span class="font-sans text-lg font-semibold leading-tight tracking-tight text-slate-950 md:text-[22px] dark:text-white">
                                    {{ $card['value'] }}
                                </span>
                            </div>
                        </div>

                        <span class="relative top-1 flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-white shadow-none dark:border-gray-700 dark:bg-gray-900 {{ $card['badge'] }}">
                            <span class="affiliate-report-kpi-icon {{ $card['icon'] }} text-xl" aria-hidden="true"></span>
                        </span>
                    </div>

                    <span class="absolute bottom-5 left-5 inline-flex max-w-[calc(100%-5.5rem)] shrink-0 truncate rounded-full bg-slate-100 px-3 py-1 text-[12px] font-semibold text-slate-600 dark:bg-gray-900 dark:text-slate-300">
                        {{ $card['helper'] }}
                    </span>
                </div>
            @endforeach
        </div>

        <v-affiliate-report-charts></v-affiliate-report-charts>

        <div class="min-w-0 overflow-hidden rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-start justify-between gap-4 max-sm:flex-wrap">
                <div>
                    <p class="text-base font-bold text-gray-900 dark:text-white">
                        Top Affiliates
                    </p>

                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Ranked by real commission totals, then attributed order volume.
                    </p>
                </div>
            </div>

            <div class="mt-5 overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead class="border-y border-gray-100 bg-gray-50 text-xs font-bold uppercase tracking-wide text-gray-500 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3">Affiliate</th>
                            <th class="px-4 py-3">Referral Code</th>
                            <th class="px-4 py-3">Clicks</th>
                            <th class="px-4 py-3">Orders</th>
                            <th class="px-4 py-3">Commission</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($topAffiliates as $profile)
                            <tr class="transition hover:bg-gray-50/70 dark:hover:bg-gray-950/60">
                                <td class="px-4 py-4">
                                    <a
                                        href="{{ route('admin.affiliates.profiles.show', $profile) }}"
                                        class="font-semibold text-[#00A4EF] hover:underline"
                                    >
                                        {{ trim(($profile->customer?->first_name ?? '').' '.($profile->customer?->last_name ?? '')) ?: 'Affiliate #'.$profile->id }}
                                    </a>
                                </td>
                                <td class="px-4 py-4 font-semibold text-gray-900 dark:text-white">{{ $profile->referral_code }}</td>
                                <td class="px-4 py-4 text-gray-600 dark:text-gray-300">{{ number_format($profile->clicks_count) }}</td>
                                <td class="px-4 py-4 text-gray-600 dark:text-gray-300">{{ number_format($profile->attributions_count) }}</td>
                                <td class="px-4 py-4 font-semibold text-gray-900 dark:text-white">{{ $formatMoney($profile->commission_total ?? 0) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-300">
                                    No affiliate performance data yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="affiliate-report-finance-grid min-w-0 items-start">
            <div class="min-w-0 overflow-hidden rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-base font-bold text-gray-900 dark:text-white">
                    Finance Snapshot
                </p>

                <div class="mt-5 grid gap-3">
                    <div class="flex items-center justify-between gap-4 rounded-xl border border-[#7FBA00]/25 bg-[#7FBA00]/10 px-4 py-3 dark:border-[#7FBA00]/35 dark:bg-[#7FBA00]/15">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Approved commission</span>
                        <span class="text-sm font-bold text-gray-950 dark:text-white">{{ $formatMoney($balance['approved_commissions'] ?? 0) }}</span>
                    </div>

                    <div class="flex items-center justify-between gap-4 rounded-xl border border-[#FFB900]/30 bg-[#FFB900]/10 px-4 py-3 dark:border-[#FFB900]/40 dark:bg-[#FFB900]/14">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Reserved payouts</span>
                        <span class="text-sm font-bold text-gray-950 dark:text-white">{{ $formatMoney($balance['reserved_payouts'] ?? 0) }}</span>
                    </div>

                    <div class="flex items-center justify-between gap-4 rounded-xl border border-[#F25022]/25 bg-[#F25022]/10 px-4 py-3 dark:border-[#F25022]/35 dark:bg-[#F25022]/15">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Reversed commission</span>
                        <span class="text-sm font-bold text-gray-950 dark:text-white">{{ $formatMoney($commissionTotals[AffiliateCommission::STATUS_REVERSED] ?? 0) }}</span>
                    </div>
                </div>
            </div>

            <div class="min-w-0 overflow-hidden rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-base font-bold text-gray-900 dark:text-white">
                    Recent Payouts
                </p>

                <div class="mt-4 divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($recentPayouts as $payout)
                        @php
                            $customerName = trim(($payout->affiliateProfile?->customer?->first_name ?? '').' '.($payout->affiliateProfile?->customer?->last_name ?? '')) ?: 'Affiliate #'.$payout->affiliate_profile_id;
                        @endphp

                        <div class="py-3 first:pt-0 last:pb-0">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $customerName }}
                                    </p>

                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $payout->payout_reference ?: 'No reference yet' }}
                                    </p>
                                </div>

                                <span class="inline-flex shrink-0 rounded-full border px-2.5 py-1 text-[11px] font-semibold {{ $statusPillClass($payout->status) }}">
                                    {{ $payout->status_label }}
                                </span>
                            </div>

                            <div class="mt-2 flex items-center justify-between gap-3 text-sm">
                                <span class="font-bold text-gray-950 dark:text-white">{{ $formatMoney($payout->amount) }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $payout->requested_at ? core()->formatDate($payout->requested_at, 'd M Y') : 'No request date' }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                            No payout records yet.
                        </p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @pushOnce('scripts')
        <script
            type="module"
            src="{{ bagisto_asset('js/chart.js') }}"
        >
        </script>

        <script type="text/x-template" id="v-affiliate-report-charts-template">
            <div class="grid min-w-0 gap-5">
                <div class="min-w-0 overflow-hidden rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-start justify-between gap-4 max-sm:flex-wrap">
                        <div>
                            <p class="text-base font-bold text-gray-900 dark:text-white">
                                Traffic, Orders and Commission Trend
                            </p>

                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Daily affiliate clicks, attributed orders, and earned commission for the selected range.
                            </p>
                        </div>

                        <ul class="affiliate-report-chart-legend" aria-label="Traffic trend chart legend">
                            <li class="affiliate-report-chart-legend-item">
                                <span class="affiliate-report-chart-swatch" style="--legend-color: #00A4EF;"></span>
                                <span>Clicks</span>
                            </li>
                            <li class="affiliate-report-chart-legend-item">
                                <span class="affiliate-report-chart-swatch" style="--legend-color: #7FBA00;"></span>
                                <span>Orders</span>
                            </li>
                            <li class="affiliate-report-chart-legend-item">
                                <span class="affiliate-report-chart-swatch" style="--legend-color: #FFB900;"></span>
                                <span>Commission</span>
                            </li>
                        </ul>
                    </div>

                    <div class="mt-6 h-72 min-w-0 rounded-xl border border-gray-100 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-950">
                        <canvas
                            ref="trafficChart"
                            class="affiliate-report-chart-canvas"
                            data-affiliate-report-chart="traffic"
                        ></canvas>
                    </div>
                </div>

                <div class="affiliate-report-secondary-chart-grid min-w-0">
                    <div class="min-w-0 overflow-hidden rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="flex items-start justify-between gap-4 max-sm:flex-wrap">
                            <div>
                                <p class="text-base font-bold text-gray-900 dark:text-white">
                                    Payout and Reversal Trend
                                </p>

                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Paid payouts and reversed commissions from real payout and commission records.
                                </p>
                            </div>

                            <ul class="affiliate-report-chart-legend" aria-label="Payout and reversal chart legend">
                                <li class="affiliate-report-chart-legend-item">
                                    <span class="affiliate-report-chart-swatch" style="--legend-color: #7FBA00;"></span>
                                    <span>Paid Out</span>
                                </li>
                                <li class="affiliate-report-chart-legend-item">
                                    <span class="affiliate-report-chart-swatch" style="--legend-color: #F25022;"></span>
                                    <span>Reversed</span>
                                </li>
                            </ul>
                        </div>

                        <div class="mt-6 h-72 min-w-0 rounded-xl border border-gray-100 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-950">
                            <canvas
                                ref="payoutChart"
                                class="affiliate-report-chart-canvas"
                                data-affiliate-report-chart="payout-reversal"
                            ></canvas>
                        </div>
                    </div>

                    <div class="min-w-0 overflow-hidden rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="flex items-start justify-between gap-4 max-sm:flex-wrap">
                            <div>
                                <p class="text-base font-bold text-gray-900 dark:text-white">
                                    Affiliate Registrations
                                </p>

                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    New affiliate applications created during the selected range.
                                </p>
                            </div>

                        </div>

                        <div class="mt-6 h-72 min-w-0 rounded-xl border border-gray-100 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-950">
                            <canvas
                                ref="registrationChart"
                                class="affiliate-report-chart-canvas"
                                data-affiliate-report-chart="registrations"
                            ></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </script>

        <script type="module">
            app.component('v-affiliate-report-charts', {
                template: '#v-affiliate-report-charts-template',

                data() {
                    return {
                        chartData: @json($reportChartData),
                        chartInstances: {},
                    };
                },

                watch: {
                    chartData: {
                        deep: true,
                        handler() {
                            this.$nextTick(() => this.prepareCharts());
                        },
                    },
                },

                mounted() {
                    this.prepareCharts();
                },

                beforeUnmount() {
                    this.destroyCharts();
                },

                methods: {
                    prepareCharts() {
                        if (! window.Chart) {
                            window.setTimeout(() => this.prepareCharts(), 50);

                            return;
                        }

                        this.destroyCharts();

                        this.chartInstances.traffic = this.createLineChart(
                            this.$refs.trafficChart,
                            this.chartData.trafficTrendDatasets,
                            {
                                moneyAxis: true,
                                tooltipLabel: (item) => {
                                    const value = Number(item.parsed.y || 0);

                                    if (item.dataset.label === 'Commission') {
                                        return `${item.dataset.label}: ${this.formatMoney(value)}`;
                                    }

                                    return `${item.dataset.label}: ${this.formatNumber(value)}`;
                                },
                            },
                        );

                        this.chartInstances.payout = this.createLineChart(
                            this.$refs.payoutChart,
                            this.chartData.payoutTrendDatasets,
                            {
                                moneyAxis: false,
                                tooltipLabel: (item) => `${item.dataset.label}: ${this.formatMoney(Number(item.parsed.y || 0))}`,
                            },
                        );

                        this.chartInstances.registrations = this.createBarChart(
                            this.$refs.registrationChart,
                            this.chartData.registrationTrendDatasets,
                        );
                    },

                    destroyCharts() {
                        Object.values(this.chartInstances).forEach((chart) => chart?.destroy());

                        this.chartInstances = {};
                    },

                    createLineChart(canvas, datasets, options = {}) {
                        if (! canvas) {
                            return undefined;
                        }

                        const scales = {
                            x: this.xScale(),
                        };

                        if (options.moneyAxis) {
                            scales.count = this.countScale('left');
                            scales.money = this.moneyScale('right', false);
                        } else {
                            scales.money = this.moneyScale('left', true);
                        }

                        return new Chart(canvas, {
                            type: 'line',
                            data: {
                                labels: this.chartData.labels,
                                datasets,
                            },
                            options: this.chartOptions({
                                scales,
                                tooltipLabel: options.tooltipLabel,
                            }),
                        });
                    },

                    createBarChart(canvas, datasets) {
                        if (! canvas) {
                            return undefined;
                        }

                        return new Chart(canvas, {
                            type: 'bar',
                            data: {
                                labels: this.chartData.labels,
                                datasets,
                            },
                            options: this.chartOptions({
                                scales: {
                                    x: this.xScale(),
                                    y: this.countScale('left'),
                                },
                                tooltipLabel: (item) => `${item.dataset.label}: ${this.formatNumber(Number(item.parsed.y || 0))}`,
                            }),
                        });
                    },

                    chartOptions({ scales, tooltipLabel }) {
                        return {
                            maintainAspectRatio: false,
                            responsive: true,
                            interaction: {
                                intersect: false,
                                mode: 'index',
                            },
                            hover: {
                                intersect: false,
                                mode: 'index',
                            },
                            plugins: {
                                legend: {
                                    display: false,
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(15, 23, 42, 0.94)',
                                    borderColor: 'rgba(148, 163, 184, 0.26)',
                                    borderWidth: 1,
                                    bodyColor: '#E2E8F0',
                                    bodyFont: {
                                        size: 12,
                                        weight: '500',
                                    },
                                    bodySpacing: 6,
                                    caretPadding: 8,
                                    caretSize: 6,
                                    cornerRadius: 10,
                                    displayColors: true,
                                    intersect: false,
                                    mode: 'index',
                                    padding: 12,
                                    titleColor: '#FFFFFF',
                                    titleFont: {
                                        size: 13,
                                        weight: '700',
                                    },
                                    callbacks: {
                                        title: (items) => {
                                            const item = items?.[0];

                                            if (! item) {
                                                return '';
                                            }

                                            return this.chartData.tooltipDates[item.dataIndex] || item.label;
                                        },
                                        label: tooltipLabel,
                                    },
                                },
                            },
                            scales,
                        };
                    },

                    xScale() {
                        return {
                            border: {
                                display: false,
                            },
                            grid: {
                                color: 'rgba(148, 163, 184, 0.16)',
                                drawTicks: false,
                            },
                            ticks: {
                                color: '#64748B',
                                font: {
                                    size: 11,
                                    weight: '600',
                                },
                                maxRotation: 0,
                                padding: 8,
                            },
                        };
                    },

                    countScale(position) {
                        return {
                            beginAtZero: true,
                            border: {
                                display: false,
                            },
                            grid: {
                                color: 'rgba(148, 163, 184, 0.16)',
                                drawTicks: false,
                            },
                            position,
                            ticks: {
                                color: '#64748B',
                                precision: 0,
                                padding: 8,
                            },
                        };
                    },

                    moneyScale(position, drawOnChartArea) {
                        return {
                            beginAtZero: true,
                            border: {
                                display: false,
                            },
                            grid: {
                                color: 'rgba(148, 163, 184, 0.16)',
                                drawOnChartArea,
                                drawTicks: false,
                            },
                            position,
                            ticks: {
                                callback: (value) => this.formatCompactMoney(Number(value || 0)),
                                color: '#64748B',
                                padding: 8,
                            },
                        };
                    },

                    formatNumber(value) {
                        return new Intl.NumberFormat(undefined, {
                            maximumFractionDigits: 0,
                        }).format(value);
                    },

                    formatMoney(value) {
                        return new Intl.NumberFormat(undefined, {
                            maximumFractionDigits: 2,
                            minimumFractionDigits: 2,
                        }).format(value);
                    },

                    formatCompactMoney(value) {
                        return new Intl.NumberFormat(undefined, {
                            maximumFractionDigits: 1,
                            notation: 'compact',
                        }).format(value);
                    },
                },
            });
        </script>

        <style>
            .affiliate-report-kpi-icon {
                color: var(--affiliate-report-color) !important;
            }

            .affiliate-report-finance-grid {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: 1.25rem;
            }

            .affiliate-report-chart-legend {
                align-items: center;
                color: rgb(107 114 128);
                cursor: default;
                display: flex;
                flex-wrap: wrap;
                font-size: 0.75rem;
                font-weight: 600;
                gap: 0.75rem;
                line-height: 1rem;
                list-style: none;
                margin: 0;
                padding: 0;
                user-select: none;
            }

            .dark .affiliate-report-chart-legend {
                color: rgb(156 163 175);
            }

            .affiliate-report-chart-legend-item {
                align-items: center;
                display: inline-flex;
                gap: 0.4rem;
                white-space: nowrap;
            }

            .affiliate-report-chart-swatch {
                background: var(--legend-color);
                border-radius: 9999px;
                display: inline-flex;
                height: 0.38rem;
                width: 1.15rem;
            }

            .affiliate-report-chart-canvas {
                height: 100% !important;
                width: 100% !important;
            }

            .affiliate-report-secondary-chart-grid {
                align-items: stretch;
                display: grid;
                gap: 1.25rem;
                grid-template-columns: minmax(0, 1fr);
            }

            .affiliate-report-secondary-chart-grid > * {
                min-width: 0;
            }

            @media (min-width: 1024px) {
                .affiliate-report-finance-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }

                .affiliate-report-secondary-chart-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }
        </style>
    @endPushOnce
</x-admin::layouts>
