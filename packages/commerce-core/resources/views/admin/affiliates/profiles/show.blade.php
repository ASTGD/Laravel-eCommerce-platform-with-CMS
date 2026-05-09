@php
    use Platform\CommerceCore\Models\AffiliateCommission;
    use Platform\CommerceCore\Models\AffiliatePayout;
    use Platform\CommerceCore\Models\AffiliateProfile;

    $identity = $dashboard['identity'];
    $referral = $dashboard['referral'];
    $settings = $dashboard['settings'];
    $commissionApprovalMode = $settings['commission_approval_mode'] ?? 'manual';
    $usesManualCommissionApproval = $commissionApprovalMode === 'manual';
    $kpis = $dashboard['kpis'];
    $currency = $dashboard['currency'];
    $approvedCommissionTotal = ($dashboard['commission_summary'][AffiliateCommission::STATUS_APPROVED] ?? 0) + ($dashboard['commission_summary'][AffiliateCommission::STATUS_PAID] ?? 0);
    $canCreatePayout = $profile->status === AffiliateProfile::STATUS_ACTIVE && bouncer()->hasPermission('affiliates.payouts.manage');
    $formatMoney = static fn ($amount, $currencyCode = null) => core()->formatPrice((float) $amount, $currencyCode ?: $currency);
    $customerName = $identity['name'];
    $applicationSource = str($identity['application_source'])->replace('_', ' ')->title()->value();
    $customerUrl = $profile->customer_id ? route('admin.customers.customers.view', $profile->customer_id) : null;
    $tabs = [
        'overview' => 'Overview',
        'commissions' => 'Commissions',
        'payouts' => 'Payouts',
        'traffic' => 'Traffic & Referrals',
        'profile' => 'Affiliate Profile',
        'activity' => 'Activity Log',
    ];
    $statusClass = match ($profile->status) {
        AffiliateProfile::STATUS_ACTIVE => 'border-[#7FBA00]/25 bg-[#7FBA00]/10 text-[#4d7100] dark:border-[#7FBA00]/40 dark:bg-[#7FBA00]/15 dark:text-[#b7e56a]',
        AffiliateProfile::STATUS_PENDING => 'border-[#FFB900]/35 bg-[#FFB900]/10 text-[#8a6400] dark:border-[#FFB900]/45 dark:bg-[#FFB900]/15 dark:text-[#ffd766]',
        AffiliateProfile::STATUS_SUSPENDED => 'border-[#737373]/25 bg-[#737373]/10 text-[#525252] dark:border-[#737373]/45 dark:bg-[#737373]/15 dark:text-[#d4d4d4]',
        AffiliateProfile::STATUS_REJECTED => 'border-[#F25022]/25 bg-[#F25022]/10 text-[#a62e12] dark:border-[#F25022]/45 dark:bg-[#F25022]/15 dark:text-[#ffb19c]',
        default => 'border-gray-200 bg-gray-50 text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200',
    };
    $tabActiveClass = 'border-[#00A4EF] bg-[#00A4EF]/10 text-[#006fa1] dark:border-[#00A4EF] dark:bg-[#00A4EF]/15 dark:text-[#8ddcff]';
    $tabInactiveClass = 'border-transparent text-gray-600 hover:border-gray-300 hover:bg-gray-50 dark:text-gray-300 dark:hover:border-gray-700 dark:hover:bg-gray-900';
    $tabClass = static fn (string $tab) => $activeTab === $tab
        ? $tabActiveClass
        : $tabInactiveClass;
    $commissionStatusLabel = static fn (AffiliateCommission $commission) => match ($commission->status) {
        AffiliateCommission::STATUS_PENDING => 'Pending Approval',
        AffiliateCommission::STATUS_REVERSED => 'Reversed',
        default => 'Approved',
    };
    $commissionStatusClass = static fn (string $status) => match ($status) {
        'Pending Approval' => 'border-[#FFB900]/35 bg-[#FFB900]/10 text-[#8a6400] dark:border-[#FFB900]/45 dark:bg-[#FFB900]/15 dark:text-[#ffd766]',
        'Approved' => 'border-[#7FBA00]/25 bg-[#7FBA00]/10 text-[#4d7100] dark:border-[#7FBA00]/40 dark:bg-[#7FBA00]/15 dark:text-[#b7e56a]',
        'Reversed' => 'border-[#F25022]/25 bg-[#F25022]/10 text-[#a62e12] dark:border-[#F25022]/45 dark:bg-[#F25022]/15 dark:text-[#ffb19c]',
        default => 'border-gray-200 bg-gray-50 text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200',
    };
    $trendRows = collect($dashboard['trend']['rows'] ?? []);
    $trendChartLabels = $trendRows->map(fn (array $row): string => core()->formatDate($row['date'], 'd M'))->all();
    $trendChartTooltipDates = $trendRows->map(fn (array $row): string => core()->formatDate($row['date'], 'd M Y'))->all();
    $trendChartDatasets = [
        [
            'label' => 'Clicks',
            'data' => $trendRows->map(fn (array $row): int => (int) ($row['clicks'] ?? 0))->all(),
            'yAxisID' => 'count',
            'borderColor' => '#00A4EF',
            'backgroundColor' => 'rgba(0, 164, 239, 0.14)',
            'borderWidth' => 2,
            'pointRadius' => 2.5,
            'pointHoverRadius' => 5,
            'pointBackgroundColor' => '#00A4EF',
            'pointBorderColor' => '#ffffff',
            'pointBorderWidth' => 1.5,
            'tension' => 0.32,
            'fill' => true,
        ],
        [
            'label' => 'Orders',
            'data' => $trendRows->map(fn (array $row): int => (int) ($row['orders'] ?? 0))->all(),
            'yAxisID' => 'count',
            'borderColor' => '#7FBA00',
            'backgroundColor' => 'rgba(127, 186, 0, 0.12)',
            'borderWidth' => 2,
            'pointRadius' => 2.5,
            'pointHoverRadius' => 5,
            'pointBackgroundColor' => '#7FBA00',
            'pointBorderColor' => '#ffffff',
            'pointBorderWidth' => 1.5,
            'tension' => 0.32,
            'fill' => true,
        ],
        [
            'label' => 'Commission',
            'data' => $trendRows->map(fn (array $row): float => round((float) ($row['commissions'] ?? 0), 4))->all(),
            'yAxisID' => 'money',
            'borderColor' => '#FFB900',
            'backgroundColor' => 'rgba(255, 185, 0, 0.12)',
            'borderWidth' => 2,
            'pointRadius' => 2.5,
            'pointHoverRadius' => 5,
            'pointBackgroundColor' => '#FFB900',
            'pointBorderColor' => '#ffffff',
            'pointBorderWidth' => 1.5,
            'tension' => 0.32,
            'fill' => true,
        ],
    ];
    $kpiCards = [
        [
            'label' => 'Total Clicks',
            'value' => number_format($kpis['total_clicks']),
            'helper' => 'Tracked referral visits',
            'color' => '#00A4EF',
            'badge' => 'bg-[#00A4EF]/12 text-[#007db7] ring-[#00A4EF]/28 dark:bg-[#00A4EF]/18 dark:text-[#8ddcff] dark:ring-[#00A4EF]/40',
            'icon' => 'cursor',
        ],
        [
            'label' => 'Unique Visitors',
            'value' => number_format($dashboard['traffic_summary']['unique_visitors']),
            'helper' => 'Distinct referral visitors',
            'color' => '#737373',
            'badge' => 'bg-[#737373]/10 text-[#5f5f5f] ring-[#737373]/24 dark:bg-[#737373]/20 dark:text-[#d4d4d4] dark:ring-[#737373]/40',
            'icon' => 'visitors',
        ],
        [
            'label' => 'Referred Orders',
            'value' => number_format($kpis['referred_orders']),
            'helper' => 'Orders attributed to this affiliate',
            'color' => '#7FBA00',
            'badge' => 'bg-[#7FBA00]/12 text-[#5f8c00] ring-[#7FBA00]/28 dark:bg-[#7FBA00]/18 dark:text-[#b7e56a] dark:ring-[#7FBA00]/40',
            'icon' => 'orders',
        ],
        [
            'label' => 'Conversion Rate',
            'value' => number_format($kpis['conversion_rate'], 2).'%',
            'helper' => 'Orders divided by tracked clicks',
            'color' => '#7FBA00',
            'badge' => 'bg-[#7FBA00]/12 text-[#5f8c00] ring-[#7FBA00]/28 dark:bg-[#7FBA00]/18 dark:text-[#b7e56a] dark:ring-[#7FBA00]/40',
            'icon' => 'conversion',
        ],
        [
            'label' => 'Commission Earned',
            'value' => $formatMoney($kpis['total_commission_earned']),
            'helper' => 'Pending, approved, and paid commission',
            'color' => '#7FBA00',
            'badge' => 'bg-[#7FBA00]/12 text-[#5f8c00] ring-[#7FBA00]/28 dark:bg-[#7FBA00]/18 dark:text-[#b7e56a] dark:ring-[#7FBA00]/40',
            'icon' => 'commission',
        ],
        [
            'label' => 'Available Balance',
            'value' => $formatMoney($kpis['available_balance']),
            'helper' => 'Currently available for payout',
            'color' => '#7FBA00',
            'badge' => 'bg-[#7FBA00]/12 text-[#5f8c00] ring-[#7FBA00]/28 dark:bg-[#7FBA00]/18 dark:text-[#b7e56a] dark:ring-[#7FBA00]/40',
            'icon' => 'balance',
        ],
        [
            'label' => 'Paid Out',
            'value' => $formatMoney($kpis['total_paid_out']),
            'helper' => 'Total completed payouts',
            'color' => '#737373',
            'badge' => 'bg-[#737373]/10 text-[#5f5f5f] ring-[#737373]/24 dark:bg-[#737373]/20 dark:text-[#d4d4d4] dark:ring-[#737373]/40',
            'icon' => 'paid',
        ],
        [
            'label' => 'Pending Requests',
            'value' => number_format($kpis['pending_withdrawals']),
            'helper' => 'Withdrawal requests waiting for admin action',
            'color' => '#FFB900',
            'badge' => 'bg-[#FFB900]/14 text-[#8a6400] ring-[#FFB900]/32 dark:bg-[#FFB900]/20 dark:text-[#ffd766] dark:ring-[#FFB900]/45',
            'icon' => 'pending',
        ],
    ];
@endphp

<x-admin::layouts>
    <x-slot:title>
        Affiliate Profile - {{ $customerName }}
    </x-slot>

    <div class="grid gap-6">
        <section class="flex flex-col gap-5 pt-1 xl:flex-row xl:items-start xl:justify-between">
            <div class="min-w-0 space-y-3">
                <div class="flex flex-wrap items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                    <a
                        href="{{ route('admin.affiliates.profiles.index') }}"
                        class="text-blue-600 hover:underline dark:text-blue-300"
                    >
                        My Affiliate
                    </a>

                    <span>/</span>

                    <a
                        href="{{ route('admin.affiliates.profiles.index', ['status' => $profile->status]) }}"
                        class="text-blue-600 hover:underline dark:text-blue-300"
                    >
                        {{ $profile->status_label }}
                    </a>

                    <span>/</span>
                    <span>{{ $customerName }}</span>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-3xl font-semibold tracking-tight text-slate-950 md:text-4xl dark:text-white">
                        Affiliate Profile
                    </h1>

                    <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold {{ $statusClass }}">
                        {{ $profile->status_label }}
                    </span>
                </div>

                <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm text-slate-500 dark:text-slate-400">
                    <span>Customer ID #{{ $identity['customer_id'] }}</span>
                    <span>Joined {{ $identity['joined_at'] ? core()->formatDate($identity['joined_at'], 'd M Y') : 'N/A' }}</span>
                    <span>Source: {{ $applicationSource }}</span>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 xl:justify-end">
                @if ($canCreatePayout)
                    <button
                        type="button"
                        class="primary-button !rounded-xl !px-4 !py-2 !text-sm !shadow-sm !shadow-blue-200/60"
                        data-affiliate-tab-trigger="payouts"
                        data-affiliate-tab-scroll-target="#payout-create"
                    >
                        Create Payout
                    </button>
                @endif

                @if ($profile->status === AffiliateProfile::STATUS_PENDING && bouncer()->hasPermission('affiliates.profiles.approve'))
                    <form method="POST" action="{{ route('admin.affiliates.profiles.approve', $profile) }}">
                        @csrf

                        <button type="submit" class="primary-button !rounded-xl !px-4 !py-2 !text-sm !shadow-sm !shadow-blue-200/60">
                            Approve
                        </button>
                    </form>
                @endif

                @if (in_array($profile->status, [AffiliateProfile::STATUS_PENDING, AffiliateProfile::STATUS_ACTIVE], true) && bouncer()->hasPermission('affiliates.profiles.reject'))
                    <form method="POST" action="{{ route('admin.affiliates.profiles.reject', $profile) }}">
                        @csrf

                        <input type="hidden" name="reason" value="Rejected by admin.">

                        <button type="submit" class="secondary-button !rounded-xl !px-4 !py-2 !text-sm">
                            Reject
                        </button>
                    </form>
                @endif

                @if ($profile->status === AffiliateProfile::STATUS_ACTIVE && bouncer()->hasPermission('affiliates.profiles.suspend'))
                    <form method="POST" action="{{ route('admin.affiliates.profiles.suspend', $profile) }}">
                        @csrf

                        <input type="hidden" name="reason" value="Suspended by admin.">

                        <button type="submit" class="secondary-button !rounded-xl !px-4 !py-2 !text-sm">
                            Suspend
                        </button>
                    </form>
                @endif

                @if (in_array($profile->status, [AffiliateProfile::STATUS_SUSPENDED, AffiliateProfile::STATUS_REJECTED], true) && bouncer()->hasPermission('affiliates.profiles.reactivate'))
                    <form method="POST" action="{{ route('admin.affiliates.profiles.reactivate', $profile) }}">
                        @csrf

                        <button type="submit" class="primary-button !rounded-xl !px-4 !py-2 !text-sm !shadow-sm !shadow-blue-200/60">
                            Reactivate
                        </button>
                    </form>
                @endif

                <details class="relative">
                    <summary class="secondary-button cursor-pointer list-none !rounded-xl !px-4 !py-2 !text-sm">
                        More Actions
                    </summary>

                    <div class="absolute right-0 z-10 mt-2 grid min-w-[240px] gap-1 rounded-lg border border-gray-200 bg-white p-2 text-sm shadow-lg dark:border-gray-800 dark:bg-gray-900">
                        <button
                            type="button"
                            class="rounded px-3 py-2 text-left text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800"
                            data-affiliate-copy-value="{{ $referral['url'] }}"
                        >
                            Copy Referral Link
                        </button>

                        @if ($customerUrl)
                            <a
                                href="{{ $customerUrl }}"
                                class="rounded px-3 py-2 text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800"
                            >
                                View Customer Account
                            </a>
                        @endif
                    </div>
                </details>
            </div>
        </section>

        @if (session('success'))
            <div class="rounded border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200">
                {{ session('success') }}
            </div>
        @endif

        <div class="affiliate-profile-hero-grid">
            <div class="affiliate-profile-hero-card affiliate-profile-identity-card">
                <div class="affiliate-profile-card-header">
                    <div>
                        <p class="affiliate-profile-eyebrow">Profile Summary</p>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                            Affiliate Identity
                        </h2>

                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-300">
                            Customer account, affiliate status, and payout setup.
                        </p>
                    </div>
                </div>

                <div class="affiliate-profile-identity-row">
                    <div class="affiliate-profile-avatar">
                        {{ $identity['initials'] }}
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-3">
                            <h2 class="truncate text-2xl font-bold text-gray-950 dark:text-white">
                                {{ $customerName }}
                            </h2>

                            <span class="affiliate-profile-status-badge {{ $statusClass }}">
                                {{ $profile->status_label }}
                            </span>
                        </div>

                        <p class="mt-1 truncate text-sm font-medium text-gray-500 dark:text-gray-300">
                            {{ $identity['email'] ?: 'No email' }}
                        </p>
                    </div>
                </div>

                <div class="affiliate-profile-info-grid">
                    <div class="affiliate-profile-info-item">
                        <p>Phone</p>
                        <strong>{{ $identity['phone'] ?: 'N/A' }}</strong>
                    </div>

                    <div class="affiliate-profile-info-item">
                        <p>Application Date</p>
                        <strong>{{ $profile->created_at ? core()->formatDate($profile->created_at, 'd M Y H:i') : 'N/A' }}</strong>
                    </div>

                    <div class="affiliate-profile-info-item">
                        <p>Approval Date</p>
                        <strong>{{ $profile->approved_at ? core()->formatDate($profile->approved_at, 'd M Y H:i') : 'N/A' }}</strong>
                    </div>

                    <div class="affiliate-profile-info-item">
                        <p>Payout Method</p>
                        <strong>{{ $profile->payout_method ? str($profile->payout_method)->replace('_', ' ')->title() : 'Not set' }}</strong>
                    </div>

                    <div class="affiliate-profile-info-item">
                        <p>Commission Rule</p>
                        <strong>{{ $settings['commission_rule'] }}</strong>
                    </div>

                    <div class="affiliate-profile-info-item">
                        <p>Minimum Payout</p>
                        <strong>{{ $formatMoney($settings['minimum_payout_amount']) }}</strong>
                    </div>
                </div>
            </div>

            <div class="affiliate-profile-hero-card affiliate-profile-referral-card">
                <div class="affiliate-profile-card-header">
                    <div>
                        <p class="affiliate-profile-eyebrow">Referral Operations</p>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                            Referral Tools
                        </h2>

                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-300">
                            Referral links stay stable and work while this affiliate is active.
                        </p>
                    </div>
                </div>

                <div class="affiliate-profile-referral-stack">
                    <div class="affiliate-profile-referral-block affiliate-profile-code-block">
                        <div class="min-w-0">
                            <p class="affiliate-profile-block-label">Referral Code</p>
                            <p class="affiliate-profile-code-value">{{ $referral['code'] }}</p>
                        </div>

                        <button
                            type="button"
                            class="affiliate-profile-utility-action"
                            data-affiliate-copy-value="{{ $referral['code'] }}"
                        >
                            Copy Code
                        </button>
                    </div>

                    <div class="affiliate-profile-referral-block">
                        <div class="min-w-0">
                            <p class="affiliate-profile-block-label">Referral Link</p>
                            <p class="affiliate-profile-link-value">{{ $referral['url'] }}</p>
                        </div>

                        <div class="flex shrink-0 flex-wrap items-center gap-2">
                            <button
                                type="button"
                                class="affiliate-profile-utility-action"
                                data-affiliate-copy-value="{{ $referral['url'] }}"
                            >
                                Copy Link
                            </button>

                            <a
                                href="{{ $referral['url'] }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="affiliate-profile-utility-action"
                            >
                                Open Link
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="affiliate-profile-kpi-grid">
            @foreach ($kpiCards as $card)
                <div
                    class="affiliate-profile-kpi-card relative overflow-hidden rounded-2xl border bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:bg-gray-900"
                    style="--affiliate-kpi-color: {{ $card['color'] }};"
                >
                    <div class="affiliate-profile-kpi-accent"></div>

                    <div class="flex h-full items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                {{ $card['label'] }}
                            </p>

                            <p class="affiliate-profile-kpi-value mt-3 break-words text-gray-900 dark:text-white">
                                {{ $card['value'] }}
                            </p>

                            <p class="mt-2 text-sm leading-5 text-gray-500 dark:text-gray-400">
                                {{ $card['helper'] }}
                            </p>
                        </div>

                        <div class="affiliate-profile-kpi-icon flex h-11 w-11 shrink-0 items-center justify-center rounded-xl ring-1 {{ $card['badge'] }}">
                            <svg
                                class="h-5 w-5"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.8"
                                stroke="currentColor"
                                aria-hidden="true"
                            >
                                @switch($card['icon'])
                                    @case('cursor')
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 4l10 7-5 1.5 3.5 5.5-2.5 1.5-3.5-5.5L6 18 7 4z" />
                                        @break

                                    @case('orders')
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 7.5h10.5l1 11h-12.5l1-11z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 9.5V7a3 3 0 116 0v2.5" />
                                        @break

                                    @case('conversion')
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 18.5h16" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.5 15l3.5-4 3 2.5 4.5-6" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7.5h1.5V9" />
                                        @break

                                    @case('visitors')
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.5 11a3 3 0 100-6 3 3 0 000 6z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.5 12a2.5 2.5 0 100-5 2.5 2.5 0 000 5z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.5 19c.75-3 2.5-4.5 5-4.5s4.25 1.5 5 4.5" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 15c2.25.25 3.75 1.6 4.5 4" />
                                        @break

                                    @case('commission')
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 9.25c-.5-.75-1.5-1.25-3-1.25-1.75 0-3 .8-3 2s1.25 1.75 3 2 3 .75 3 2-1.25 2-3 2c-1.5 0-2.5-.5-3-1.25" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        @break

                                    @case('balance')
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 7.5h15v10h-15v-10z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12h.01" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 7.5V6h10v1.5" />
                                        @break

                                    @case('paid')
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 12.5l3 3 7-7" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        @break

                                    @default
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                @endswitch
                            </svg>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div
            class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900"
            data-affiliate-profile-tabs
            data-initial-tab="{{ $activeTab }}"
        >
            <div class="overflow-x-auto border-b border-gray-200 px-4 dark:border-gray-800">
                <div
                    class="flex min-w-max gap-2"
                    role="tablist"
                    aria-label="Affiliate profile sections"
                >
                    @foreach ($tabs as $tabKey => $tabLabel)
                        <button
                            type="button"
                            id="affiliate-tab-trigger-{{ $tabKey }}"
                            class="border-b-2 px-4 py-3 text-sm font-semibold {{ $tabClass($tabKey) }}"
                            data-affiliate-tab-trigger="{{ $tabKey }}"
                            data-affiliate-tab-nav
                            role="tab"
                            aria-selected="{{ $activeTab === $tabKey ? 'true' : 'false' }}"
                            aria-controls="affiliate-tab-panel-{{ $tabKey }}"
                        >
                            {{ $tabLabel }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="p-5">
                <section
                    id="affiliate-tab-panel-overview"
                    data-affiliate-tab-panel="overview"
                    role="tabpanel"
                    aria-labelledby="affiliate-tab-trigger-overview"
                    class="{{ $activeTab === 'overview' ? '' : 'hidden' }}"
                >
                    <div class="affiliate-profile-overview-grid">
                        <div class="affiliate-profile-main-stack">
                            <div class="affiliate-profile-card">
                                <div class="flex items-start justify-between gap-3 max-sm:flex-wrap">
                                    <div>
                                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                            Performance Trend
                                        </h3>

                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-300">
                                            Clicks, referred orders, and commission over the last 14 days.
                                        </p>
                                    </div>
                                </div>

                                <ul class="affiliate-profile-chart-legend mt-4" aria-label="Performance trend chart legend">
                                    <li class="affiliate-profile-chart-legend-item">
                                        <span class="affiliate-profile-chart-swatch" style="--legend-color: #00A4EF;"></span>
                                        <span>Clicks</span>
                                    </li>
                                    <li class="affiliate-profile-chart-legend-item">
                                        <span class="affiliate-profile-chart-swatch" style="--legend-color: #7FBA00;"></span>
                                        <span>Orders</span>
                                    </li>
                                    <li class="affiliate-profile-chart-legend-item">
                                        <span class="affiliate-profile-chart-swatch" style="--legend-color: #FFB900;"></span>
                                        <span>Commission</span>
                                    </li>
                                </ul>

                                <div class="affiliate-profile-trend-chart mt-5">
                                    <v-affiliate-profile-performance-trend
                                        :labels='@json($trendChartLabels)'
                                        :tooltip-dates='@json($trendChartTooltipDates)'
                                        :datasets='@json($trendChartDatasets)'
                                        currency-code="{{ $currency }}"
                                    ></v-affiliate-profile-performance-trend>
                                </div>
                            </div>

                            <div class="affiliate-profile-card">
                                <div class="flex items-start justify-between gap-3 max-sm:flex-wrap">
                                    <div>
                                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                            Recent Referred Orders
                                        </h3>

                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-300">
                                            Latest attributed orders and their commission state.
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-4 overflow-x-auto">
                                    <table class="w-full min-w-[760px] text-left text-sm">
                                        <thead class="border-b border-gray-200 text-xs uppercase text-gray-500 dark:border-gray-800 dark:text-gray-400">
                                            <tr>
                                                <th class="px-3 py-2">Order No</th>
                                                <th class="px-3 py-2">Customer</th>
                                                <th class="px-3 py-2">Order Total</th>
                                                <th class="px-3 py-2">Commission</th>
                                                <th class="px-3 py-2">Order Date</th>
                                                <th class="px-3 py-2">Status</th>
                                            </tr>
                                        </thead>

                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                            @forelse ($dashboard['recent_orders'] as $row)
                                                <tr>
                                                    <td class="px-3 py-3">
                                                        @if ($row['order'])
                                                            <a href="{{ route('admin.sales.orders.view', $row['order']->id) }}" class="font-medium text-blue-600 hover:underline">
                                                                #{{ $row['order']->increment_id }}
                                                            </a>
                                                        @else
                                                            Order #{{ $row['attribution']->order_id }}
                                                        @endif
                                                    </td>
                                                    <td class="px-3 py-3 text-gray-700 dark:text-gray-200">{{ $row['order']?->customer_full_name ?: 'Guest / deleted customer' }}</td>
                                                    <td class="px-3 py-3 text-gray-700 dark:text-gray-200">{{ $formatMoney($row['order']?->base_grand_total ?? 0, $row['order']?->base_currency_code ?: $currency) }}</td>
                                                    <td class="px-3 py-3 text-gray-700 dark:text-gray-200">{{ $formatMoney($row['commission']?->commission_amount ?? 0, $row['commission']?->currency ?: $currency) }}</td>
                                                    <td class="px-3 py-3 text-gray-500">{{ $row['order']?->created_at ? core()->formatDate($row['order']->created_at, 'd M Y') : 'N/A' }}</td>
                                                    <td class="px-3 py-3 text-gray-700 dark:text-gray-200">{{ $row['commission']?->status_label ?: $row['attribution']->status }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="px-3 py-8 text-center text-gray-500">
                                                        No referred orders yet.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="affiliate-profile-side-stack">
                            <div class="affiliate-profile-card">
                                <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                    Payout Summary
                                </h3>

                                <div class="mt-4 grid gap-3 text-sm">
                                    <div class="flex justify-between gap-3">
                                        <span class="text-gray-500 dark:text-gray-300">Available</span>
                                        <span class="font-semibold text-gray-900 dark:text-white">{{ $formatMoney($dashboard['balance']['available_balance']) }}</span>
                                    </div>
                                    <div class="flex justify-between gap-3">
                                        <span class="text-gray-500 dark:text-gray-300">Requested</span>
                                        <span class="font-semibold text-gray-900 dark:text-white">{{ $formatMoney($dashboard['payout_summary'][AffiliatePayout::STATUS_REQUESTED]) }}</span>
                                    </div>
                                    <div class="flex justify-between gap-3">
                                        <span class="text-gray-500 dark:text-gray-300">Approved</span>
                                        <span class="font-semibold text-gray-900 dark:text-white">{{ $formatMoney($dashboard['payout_summary'][AffiliatePayout::STATUS_APPROVED]) }}</span>
                                    </div>
                                    <div class="flex justify-between gap-3">
                                        <span class="text-gray-500 dark:text-gray-300">Paid Out</span>
                                        <span class="font-semibold text-gray-900 dark:text-white">{{ $formatMoney($dashboard['payout_summary'][AffiliatePayout::STATUS_PAID]) }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="affiliate-profile-card">
                                <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                    Latest Payout
                                </h3>

                                @if ($dashboard['latest_payout'])
                                    <div class="mt-4 grid gap-2 text-sm text-gray-600 dark:text-gray-300">
                                        <p><span class="font-semibold text-gray-900 dark:text-white">Reference:</span> {{ $dashboard['latest_payout']->payout_reference ?: 'N/A' }}</p>
                                        <p><span class="font-semibold text-gray-900 dark:text-white">Status:</span> {{ $dashboard['latest_payout']->status_label }}</p>
                                        <p><span class="font-semibold text-gray-900 dark:text-white">Amount:</span> {{ $formatMoney($dashboard['latest_payout']->amount, $dashboard['latest_payout']->currency ?: $currency) }}</p>
                                    </div>
                                @else
                                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-300">No payout recorded yet.</p>
                                @endif
                            </div>

                            <div class="affiliate-profile-card">
                                <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                    Latest Withdrawal Request
                                </h3>

                                @if ($dashboard['latest_withdrawal'])
                                    <div class="mt-4 grid gap-2 text-sm text-gray-600 dark:text-gray-300">
                                        <p><span class="font-semibold text-gray-900 dark:text-white">Reference:</span> {{ $dashboard['latest_withdrawal']->payout_reference ?: 'N/A' }}</p>
                                        <p><span class="font-semibold text-gray-900 dark:text-white">Status:</span> {{ $dashboard['latest_withdrawal']->status_label }}</p>
                                        <p><span class="font-semibold text-gray-900 dark:text-white">Requested:</span> {{ $dashboard['latest_withdrawal']->requested_at ? core()->formatDate($dashboard['latest_withdrawal']->requested_at, 'd M Y') : 'N/A' }}</p>
                                    </div>
                                @else
                                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-300">No customer withdrawal request yet.</p>
                                @endif
                            </div>

                            <div class="affiliate-profile-card">
                                <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                    Recent Activity
                                </h3>

                                <div class="mt-4 grid gap-3">
                                    @foreach ($dashboard['activity']->take(4) as $entry)
                                        <div class="border-l-2 border-blue-200 pl-3">
                                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $entry['title'] }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $entry['timestamp'] ? core()->formatDate($entry['timestamp'], 'd M Y H:i') : 'N/A' }} by {{ $entry['actor'] }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section
                    id="affiliate-tab-panel-commissions"
                    data-affiliate-tab-panel="commissions"
                    role="tabpanel"
                    aria-labelledby="affiliate-tab-trigger-commissions"
                    class="{{ $activeTab === 'commissions' ? '' : 'hidden' }}"
                >
                    <div class="affiliate-profile-main-stack">
                        <div class="affiliate-profile-section-heading">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                Commission Ledger
                            </h3>

                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-300">
                                Filter and review order-based affiliate commission records.
                            </p>
                        </div>

                        <div class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-800 dark:border-blue-900 dark:bg-blue-950 dark:text-blue-100">
                            @if ($usesManualCommissionApproval)
                                Commission approval is set to <span class="font-semibold">Manual</span>. Pending commissions require admin approval before they become available for payout.
                            @else
                                Commission approval is set to <span class="font-semibold">Automatic</span>. Commissions are approved automatically when eligible orders are completed or delivered.
                            @endif
                        </div>

                        <div class="affiliate-profile-summary-grid">
                            <div class="affiliate-profile-card">
                                <p class="text-xs uppercase text-gray-500">Total Commissions</p>
                                <p class="mt-2 text-xl font-bold text-gray-900 dark:text-white">{{ $formatMoney($kpis['total_commission_earned']) }}</p>
                            </div>
                            <div class="affiliate-profile-card">
                                <p class="text-xs uppercase text-gray-500">Pending</p>
                                <p class="mt-2 text-xl font-bold text-gray-900 dark:text-white">{{ $formatMoney($dashboard['commission_summary'][AffiliateCommission::STATUS_PENDING]) }}</p>
                            </div>
                            <div class="affiliate-profile-card">
                                <p class="text-xs uppercase text-gray-500">Approved</p>
                                <p class="mt-2 text-xl font-bold text-gray-900 dark:text-white">{{ $formatMoney($approvedCommissionTotal) }}</p>
                            </div>
                            <div class="affiliate-profile-card">
                                <p class="text-xs uppercase text-gray-500">Reversed</p>
                                <p class="mt-2 text-xl font-bold text-gray-900 dark:text-white">{{ $formatMoney($dashboard['commission_summary'][AffiliateCommission::STATUS_REVERSED]) }}</p>
                            </div>
                        </div>

                        <form method="GET" action="{{ route('admin.affiliates.profiles.show', $profile) }}" class="affiliate-profile-filter-bar">
                            <input type="hidden" name="tab" value="commissions">

                            <div class="affiliate-profile-filter-grid">
                                <label class="affiliate-profile-filter-field">
                                    <span>Order No</span>
                                    <input name="commission_order" value="{{ $commissionFilters['order'] }}" placeholder="Search order">
                                </label>

                                <label class="affiliate-profile-filter-field">
                                    <span>Status</span>
                                    <select name="commission_status" class="custom-select">
                                        <option value="">All statuses</option>
                                        @foreach ($commissionStatusOptions as $statusCode => $statusLabel)
                                            <option value="{{ $statusCode }}" @selected($commissionFilters['status'] === $statusCode)>{{ $statusLabel }}</option>
                                        @endforeach
                                    </select>
                                </label>

                                <label class="affiliate-profile-filter-field">
                                    <span>From</span>
                                    <input type="date" name="commission_date_from" value="{{ $commissionFilters['date_from'] }}">
                                </label>

                                <label class="affiliate-profile-filter-field">
                                    <span>To</span>
                                    <input type="date" name="commission_date_to" value="{{ $commissionFilters['date_to'] }}">
                                </label>

                                <div class="affiliate-profile-filter-actions">
                                    <button type="submit" class="secondary-button justify-center">Apply</button>

                                    <a
                                        href="{{ route('admin.affiliates.profiles.show', ['affiliateProfile' => $profile, 'tab' => 'commissions']) }}"
                                        class="affiliate-profile-filter-reset"
                                    >
                                        Reset
                                    </a>
                                </div>
                            </div>
                        </form>

                        <div class="affiliate-profile-card affiliate-profile-table-card">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                Commission Records
                            </h3>

                            <div class="mt-4 overflow-x-auto">
                                <table class="w-full min-w-[860px] text-left text-sm">
                                    <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-400">
                                        <tr>
                                            <th class="px-4 py-3">Order No</th>
                                            <th class="px-4 py-3">Order Date</th>
                                            <th class="px-4 py-3">Order Amount</th>
                                            <th class="px-4 py-3">Commission Amount</th>
                                            <th class="px-4 py-3">Approval Date</th>
                                            <th class="px-4 py-3">Commission Status</th>
                                            <th class="px-4 py-3 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                        @forelse ($dashboard['commission_rows'] as $commission)
                                            @php
                                                $commissionCurrency = $commission->currency ?: $currency;
                                                $workflowStatus = $commissionStatusLabel($commission);
                                            @endphp

                                            <tr class="bg-white dark:bg-gray-900">
                                                <td class="px-4 py-3">{{ $commission->order?->increment_id ? '#'.$commission->order->increment_id : 'Order #'.$commission->order_id }}</td>
                                                <td class="px-4 py-3">{{ $commission->order?->created_at ? core()->formatDate($commission->order->created_at, 'd M Y') : 'N/A' }}</td>
                                                <td class="px-4 py-3">{{ $formatMoney($commission->order_amount, $commissionCurrency) }}</td>
                                                <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">{{ $formatMoney($commission->commission_amount, $commissionCurrency) }}</td>
                                                <td class="px-4 py-3">
                                                    {{ $commission->approved_at ? core()->formatDate($commission->approved_at, 'd M Y') : 'N/A' }}
                                                </td>
                                                <td class="px-4 py-3">
                                                    <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold {{ $commissionStatusClass($workflowStatus) }}">
                                                        {{ $workflowStatus }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-right">
                                                    <div class="flex flex-wrap items-center justify-end gap-2">
                                                        @if ($commission->status === AffiliateCommission::STATUS_PENDING && $usesManualCommissionApproval && bouncer()->hasPermission('affiliates.commissions.manage'))
                                                            <form method="POST" action="{{ route('admin.affiliates.commissions.approve', $commission) }}">
                                                                @csrf
                                                                <button type="submit" class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200">
                                                                    Approve
                                                                </button>
                                                            </form>
                                                        @endif

                                                        @if (in_array($commission->status, [AffiliateCommission::STATUS_PENDING, AffiliateCommission::STATUS_APPROVED, AffiliateCommission::STATUS_PAID], true) && bouncer()->hasPermission('affiliates.commissions.manage'))
                                                            <form method="POST" action="{{ route('admin.affiliates.commissions.reverse', $commission) }}">
                                                                @csrf
                                                                <input type="hidden" name="reason" value="Reversed manually by admin.">
                                                                <button type="submit" class="rounded-md border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100 dark:border-red-900 dark:bg-red-950 dark:text-red-200">
                                                                    Reverse
                                                                </button>
                                                            </form>
                                                        @endif

                                                        @if ($commission->order)
                                                            <a href="{{ route('admin.sales.orders.view', $commission->order->id) }}" class="rounded-md border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-200 dark:hover:bg-gray-900">View Order</a>
                                                        @else
                                                            <span class="text-gray-400">N/A</span>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="px-4 py-10 text-center text-gray-500">
                                                    No commissions match the selected filters.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>

                <section
                    id="affiliate-tab-panel-payouts"
                    data-affiliate-tab-panel="payouts"
                    role="tabpanel"
                    aria-labelledby="affiliate-tab-trigger-payouts"
                    class="{{ $activeTab === 'payouts' ? '' : 'hidden' }}"
                >
                    <div class="affiliate-profile-content-grid">
                        <div class="affiliate-profile-main-stack">
                            <div class="affiliate-profile-section-heading">
                                <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                    Payout Operations
                                </h3>

                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-300">
                                    Review payout requests, paid records, and affiliate balance movement.
                                </p>
                            </div>

                            <div class="affiliate-profile-summary-grid">
                                <div class="affiliate-profile-card">
                                    <p class="text-xs uppercase text-gray-500">Available Balance</p>
                                    <p class="mt-2 text-xl font-bold text-gray-900 dark:text-white">{{ $formatMoney($dashboard['balance']['available_balance']) }}</p>
                                </div>
                                <div class="affiliate-profile-card">
                                    <p class="text-xs uppercase text-gray-500">Requested</p>
                                    <p class="mt-2 text-xl font-bold text-gray-900 dark:text-white">{{ $formatMoney($dashboard['payout_summary'][AffiliatePayout::STATUS_REQUESTED]) }}</p>
                                </div>
                                <div class="affiliate-profile-card">
                                    <p class="text-xs uppercase text-gray-500">Approved</p>
                                    <p class="mt-2 text-xl font-bold text-gray-900 dark:text-white">{{ $formatMoney($dashboard['payout_summary'][AffiliatePayout::STATUS_APPROVED]) }}</p>
                                </div>
                                <div class="affiliate-profile-card">
                                    <p class="text-xs uppercase text-gray-500">Paid Out</p>
                                    <p class="mt-2 text-xl font-bold text-gray-900 dark:text-white">{{ $formatMoney($dashboard['payout_summary'][AffiliatePayout::STATUS_PAID]) }}</p>
                                </div>
                            </div>

                            <div class="affiliate-profile-card affiliate-profile-table-card">
                                <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                    Payout History
                                </h3>

                                <div class="mt-4 overflow-x-auto">
                                    <table class="w-full min-w-[1220px] text-left text-sm">
                                        <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-400">
                                            <tr>
                                                <th class="px-4 py-3">Payout Reference</th>
                                                <th class="px-4 py-3">Request Date</th>
                                                <th class="px-4 py-3">Amount</th>
                                                <th class="px-4 py-3">Payout Method</th>
                                                <th class="px-4 py-3">Transaction No</th>
                                                <th class="px-4 py-3">Status</th>
                                                <th class="px-4 py-3">Completed Date</th>
                                                <th class="px-4 py-3">Notes</th>
                                                <th class="px-4 py-3">Covered Commissions</th>
                                                <th class="px-4 py-3 text-right">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                            @forelse ($dashboard['payout_rows'] as $payout)
                                                @php
                                                    $payoutCurrency = $payout->currency ?: $currency;
                                                    $payoutAllocations = $payout->allocations->sortBy('id')->values();
                                                @endphp

                                                <tr>
                                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $payout->payout_reference ?: 'N/A' }}</td>
                                                    <td class="px-4 py-3">{{ $payout->requested_at ? core()->formatDate($payout->requested_at, 'd M Y') : core()->formatDate($payout->created_at, 'd M Y') }}</td>
                                                    <td class="px-4 py-3">{{ $formatMoney($payout->amount, $payoutCurrency) }}</td>
                                                    <td class="px-4 py-3">{{ $payout->payout_method ? str($payout->payout_method)->replace('_', ' ')->title() : 'N/A' }}</td>
                                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $payout->transaction_reference ?: 'N/A' }}</td>
                                                    <td class="px-4 py-3">{{ $payout->status_label }}</td>
                                                    <td class="px-4 py-3">{{ $payout->paid_at ? core()->formatDate($payout->paid_at, 'd M Y') : 'N/A' }}</td>
                                                    <td class="px-4 py-3">{{ $payout->admin_notes ?: $payout->notes ?: 'N/A' }}</td>
                                                    <td class="px-4 py-3">
                                                        @if ($payoutAllocations->isNotEmpty())
                                                            <button
                                                                type="button"
                                                                class="text-xs font-semibold text-blue-600 hover:text-blue-700 hover:underline dark:text-blue-300 dark:hover:text-blue-200"
                                                                data-affiliate-payout-allocation-toggle="{{ $payout->id }}"
                                                                aria-expanded="false"
                                                            >
                                                                See Allocations
                                                            </button>

                                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                                {{ $payoutAllocations->count() }} {{ str('commission')->plural($payoutAllocations->count()) }}
                                                            </p>
                                                        @else
                                                            <span class="text-gray-400">N/A</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-3 text-right">
                                                        @if (bouncer()->hasPermission('affiliates.payouts.manage') && $payout->status === AffiliatePayout::STATUS_REQUESTED)
                                                            <div class="flex justify-end gap-2">
                                                                <form method="POST" action="{{ route('admin.affiliates.payouts.approve', $payout) }}">
                                                                    @csrf
                                                                    <button type="submit" class="text-blue-600 hover:underline">Approve</button>
                                                                </form>
                                                                <form method="POST" action="{{ route('admin.affiliates.payouts.mark-paid', $payout) }}" class="flex items-center justify-end gap-2">
                                                                    @csrf
                                                                    <input name="transaction_reference" type="text" class="w-40 rounded-md border px-2 py-1.5 text-xs dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300" placeholder="Transaction no" required>
                                                                    <button type="submit" class="text-emerald-600 hover:underline">Mark Paid</button>
                                                                </form>
                                                            </div>
                                                        @elseif (bouncer()->hasPermission('affiliates.payouts.manage') && $payout->status === AffiliatePayout::STATUS_APPROVED)
                                                            <form method="POST" action="{{ route('admin.affiliates.payouts.mark-paid', $payout) }}" class="flex items-center justify-end gap-2">
                                                                @csrf
                                                                <input name="transaction_reference" type="text" class="w-40 rounded-md border px-2 py-1.5 text-xs dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300" placeholder="Transaction no" required>
                                                                <button type="submit" class="text-emerald-600 hover:underline">Mark Paid</button>
                                                            </form>
                                                        @else
                                                            <span class="text-gray-400">N/A</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                @if ($payoutAllocations->isNotEmpty())
                                                    <tr
                                                        class="hidden bg-slate-50/50 dark:bg-gray-950/40"
                                                        data-affiliate-payout-allocation-row="{{ $payout->id }}"
                                                    >
                                                        <td colspan="10" class="px-4 pb-4 pt-0">
                                                            <div class="ml-4 border-l border-dashed border-gray-300 pl-4 dark:border-gray-700">
                                                                <div class="overflow-x-auto rounded-md border border-gray-100 bg-white dark:border-gray-800 dark:bg-gray-900">
                                                                    <table class="w-full min-w-[760px] text-left text-xs">
                                                                        <thead class="border-b border-gray-100 bg-gray-50 text-[10px] font-semibold uppercase tracking-wide text-gray-400 dark:border-gray-800 dark:bg-gray-950">
                                                                            <tr>
                                                                                <th class="px-3 py-2">Order No</th>
                                                                                <th class="px-3 py-2">Order Date</th>
                                                                                <th class="px-3 py-2">Commission Amount</th>
                                                                                <th class="px-3 py-2">Allocation Amount</th>
                                                                                <th class="px-3 py-2">Allocation Status</th>
                                                                                <th class="px-3 py-2">Commission Status</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                                                            @foreach ($payoutAllocations as $allocation)
                                                                                @php
                                                                                    $allocatedCommission = $allocation->commission;
                                                                                    $allocationCurrency = $allocatedCommission?->currency ?: $payoutCurrency;
                                                                                    $allocatedCommissionStatus = $allocatedCommission ? $commissionStatusLabel($allocatedCommission) : 'N/A';
                                                                                @endphp

                                                                                <tr class="text-gray-600 dark:text-gray-300">
                                                                                    <td class="px-3 py-2 font-semibold text-gray-900 dark:text-white">
                                                                                        {{ $allocatedCommission?->order?->increment_id ? '#'.$allocatedCommission->order->increment_id : ($allocatedCommission?->order_id ? 'Order #'.$allocatedCommission->order_id : 'N/A') }}
                                                                                    </td>
                                                                                    <td class="px-3 py-2">{{ $allocatedCommission?->order?->created_at ? core()->formatDate($allocatedCommission->order->created_at, 'd M Y') : 'N/A' }}</td>
                                                                                    <td class="px-3 py-2">{{ $allocatedCommission ? $formatMoney($allocatedCommission->commission_amount, $allocationCurrency) : 'N/A' }}</td>
                                                                                    <td class="px-3 py-2 font-semibold text-gray-900 dark:text-white">{{ $formatMoney($allocation->amount, $allocationCurrency) }}</td>
                                                                                    <td class="px-3 py-2">{{ $allocation->status_label }}</td>
                                                                                    <td class="px-3 py-2">
                                                                                        @if ($allocatedCommission)
                                                                                            <span class="inline-flex rounded-full border px-2 py-0.5 text-[11px] font-semibold {{ $commissionStatusClass($allocatedCommissionStatus) }}">
                                                                                                {{ $allocatedCommissionStatus }}
                                                                                            </span>
                                                                                        @else
                                                                                            N/A
                                                                                        @endif
                                                                                    </td>
                                                                                </tr>
                                                                            @endforeach
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endif
                                            @empty
                                                <tr>
                                                    <td colspan="10" class="px-4 py-10 text-center text-gray-500">
                                                        No payouts recorded yet.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="affiliate-profile-side-stack">
                            <div class="affiliate-profile-card">
                                <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                    Preferred Payout Details
                                </h3>
                                <div class="mt-4 grid gap-2 text-sm text-gray-600 dark:text-gray-300">
                                    <p><span class="font-semibold text-gray-900 dark:text-white">Method:</span> {{ $profile->payout_method ? str($profile->payout_method)->replace('_', ' ')->title() : 'Not set' }}</p>
                                    <p><span class="font-semibold text-gray-900 dark:text-white">Account:</span> {{ $profile->payout_reference ?: 'Not set' }}</p>
                                    <p><span class="font-semibold text-gray-900 dark:text-white">Last payout:</span> {{ $dashboard['latest_payout']?->paid_at ? core()->formatDate($dashboard['latest_payout']->paid_at, 'd M Y') : 'N/A' }}</p>
                                </div>
                            </div>

                            @if ($canCreatePayout)
                                <div id="payout-create" class="affiliate-profile-card">
                                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                        Create Payout
                                    </h3>

                                    <form method="POST" action="{{ route('admin.affiliates.profiles.payouts.store', $profile) }}" class="mt-4 grid gap-3">
                                        @csrf
                                        <input name="amount" type="number" step="0.01" min="0" value="{{ old('amount') }}" class="w-full rounded-md border px-3 py-2.5 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300" placeholder="Amount">
                                        @error('amount') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

                                        <select name="payout_method" class="custom-select w-full rounded-md border bg-white px-3 py-2.5 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                                            @foreach ($payoutMethods as $methodCode => $methodLabel)
                                                <option value="{{ $methodCode }}" @selected(old('payout_method', $profile->payout_method) === $methodCode)>{{ $methodLabel }}</option>
                                            @endforeach
                                        </select>
                                        @error('payout_method') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

                                        <input name="currency" type="text" value="{{ old('currency', $currency) }}" class="w-full rounded-md border px-3 py-2.5 text-sm uppercase dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300" placeholder="Currency">
                                        @error('currency') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

                                        <input name="payout_reference" type="text" value="{{ old('payout_reference') }}" class="w-full rounded-md border px-3 py-2.5 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300" placeholder="Payout reference (optional)">
                                        @error('payout_reference') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

                                        <input name="transaction_reference" type="text" value="{{ old('transaction_reference') }}" class="w-full rounded-md border px-3 py-2.5 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300" placeholder="Transaction number / reference" required>
                                        @error('transaction_reference') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

                                        <textarea name="admin_notes" rows="3" class="w-full rounded-md border px-3 py-2.5 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300" placeholder="Admin note">{{ old('admin_notes') }}</textarea>

                                        <button type="submit" class="primary-button justify-center">Create Paid Payout</button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </div>
                </section>

                <section
                    id="affiliate-tab-panel-traffic"
                    data-affiliate-tab-panel="traffic"
                    role="tabpanel"
                    aria-labelledby="affiliate-tab-trigger-traffic"
                    class="{{ $activeTab === 'traffic' ? '' : 'hidden' }}"
                >
                    <div class="affiliate-profile-main-stack">
                        <div class="affiliate-profile-section-heading">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                Recent Referral Activity
                            </h3>

                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-300">
                                Track clicks, attributed orders, and conversion behavior for this affiliate.
                            </p>
                        </div>

                        <div class="affiliate-profile-summary-grid">
                            <div class="affiliate-profile-card">
                                <p class="text-xs uppercase text-gray-500">Total Clicks</p>
                                <p class="mt-2 text-xl font-bold text-gray-900 dark:text-white">{{ number_format($dashboard['traffic_summary']['total_clicks']) }}</p>
                            </div>
                            <div class="affiliate-profile-card">
                                <p class="text-xs uppercase text-gray-500">Unique Visitors</p>
                                <p class="mt-2 text-xl font-bold text-gray-900 dark:text-white">{{ number_format($dashboard['traffic_summary']['unique_visitors']) }}</p>
                            </div>
                            <div class="affiliate-profile-card">
                                <p class="text-xs uppercase text-gray-500">Attributed Orders</p>
                                <p class="mt-2 text-xl font-bold text-gray-900 dark:text-white">{{ number_format($dashboard['sales_summary']['attributed_orders']) }}</p>
                            </div>
                            <div class="affiliate-profile-card">
                                <p class="text-xs uppercase text-gray-500">Conversion Rate</p>
                                <p class="mt-2 text-xl font-bold text-gray-900 dark:text-white">{{ number_format($kpis['conversion_rate'], 2) }}%</p>
                            </div>
                        </div>

                        <div class="affiliate-profile-content-grid">
                            <div class="affiliate-profile-card">
                                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Traffic Trend</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-300">
                                    Daily referral clicks over the last 14 days.
                                </p>

                                <div class="affiliate-profile-traffic-chart mt-5">
                                    @foreach ($dashboard['trend']['rows'] as $row)
                                        <div
                                            class="affiliate-profile-traffic-column"
                                            title="{{ core()->formatDate($row['date'], 'd M Y') }}: {{ $row['clicks'] }} clicks"
                                        >
                                            <div class="affiliate-profile-traffic-bar">
                                                <span style="height: {{ max(8, min(100, ($row['clicks'] / $dashboard['trend']['max_clicks']) * 100)) }}%"></span>
                                            </div>
                                            <p class="text-center text-[10px] font-medium text-gray-500 dark:text-gray-400">{{ core()->formatDate($row['date'], 'd M') }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="affiliate-profile-side-stack">
                                <div class="affiliate-profile-card">
                                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Referral Quality</h3>
                                    <div class="mt-4 grid gap-3 text-sm">
                                        <div class="flex justify-between gap-3">
                                            <span class="text-gray-500 dark:text-gray-300">Cookie window</span>
                                            <span class="font-semibold text-gray-900 dark:text-white">{{ $settings['cookie_window_days'] }} days</span>
                                        </div>
                                        <div class="flex justify-between gap-3">
                                            <span class="text-gray-500 dark:text-gray-300">Valid code</span>
                                            <span class="font-semibold text-gray-900 dark:text-white">{{ $referral['code'] }}</span>
                                        </div>
                                        <div class="flex justify-between gap-3">
                                            <span class="text-gray-500 dark:text-gray-300">Attribution rate</span>
                                            <span class="font-semibold text-gray-900 dark:text-white">{{ number_format($kpis['conversion_rate'], 2) }}%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="affiliate-profile-card affiliate-profile-table-card">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                Recent Referral Activity
                            </h3>

                            <div class="mt-4 overflow-x-auto">
                                <table class="w-full min-w-[900px] text-left text-sm">
                                    <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase text-gray-500 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-400">
                                        <tr>
                                            <th class="px-4 py-3">Date</th>
                                            <th class="px-4 py-3">Landing Page</th>
                                            <th class="px-4 py-3">Referral Code</th>
                                            <th class="px-4 py-3">Attributed Order</th>
                                            <th class="px-4 py-3">Order No</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                        @forelse ($dashboard['traffic_rows'] as $click)
                                            @php($attribution = $click->attributions->first())
                                            <tr>
                                                <td class="px-4 py-3">{{ $click->clicked_at ? core()->formatDate($click->clicked_at, 'd M Y H:i') : 'N/A' }}</td>
                                                <td class="px-4 py-3 break-all">{{ $click->landing_url ?: 'N/A' }}</td>
                                                <td class="px-4 py-3">{{ $click->referral_code }}</td>
                                                <td class="px-4 py-3">{{ $attribution ? 'Yes' : 'No' }}</td>
                                                <td class="px-4 py-3">
                                                    @if ($attribution?->order)
                                                        <a href="{{ route('admin.sales.orders.view', $attribution->order->id) }}" class="text-blue-600 hover:underline">#{{ $attribution->order->increment_id }}</a>
                                                    @else
                                                        N/A
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="px-4 py-10 text-center text-gray-500">No referral clicks yet.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>

                <section
                    id="affiliate-tab-panel-profile"
                    data-affiliate-tab-panel="profile"
                    role="tabpanel"
                    aria-labelledby="affiliate-tab-trigger-profile"
                    class="{{ $activeTab === 'profile' ? '' : 'hidden' }}"
                >
                    <div class="affiliate-profile-profile-grid">
                        <div class="affiliate-profile-card">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Customer Info</h3>
                            <div class="mt-4 grid gap-2 text-sm text-gray-600 dark:text-gray-300">
                                <p><span class="font-semibold text-gray-900 dark:text-white">Name:</span> {{ $customerName }}</p>
                                <p><span class="font-semibold text-gray-900 dark:text-white">Email:</span> {{ $identity['email'] ?: 'N/A' }}</p>
                                <p><span class="font-semibold text-gray-900 dark:text-white">Phone:</span> {{ $identity['phone'] ?: 'N/A' }}</p>
                                <p><span class="font-semibold text-gray-900 dark:text-white">Customer ID:</span> #{{ $identity['customer_id'] }}</p>
                                <p><span class="font-semibold text-gray-900 dark:text-white">Account Created:</span> {{ $identity['customer_created_at'] ? core()->formatDate($identity['customer_created_at'], 'd M Y H:i') : 'N/A' }}</p>
                            </div>
                        </div>

                        <div class="affiliate-profile-card">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Application Info</h3>
                            <div class="mt-4 grid gap-3 text-sm text-gray-600 dark:text-gray-300">
                                <p><span class="font-semibold text-gray-900 dark:text-white">Website:</span> {{ $profile->website_url ?: 'N/A' }}</p>
                                <p><span class="font-semibold text-gray-900 dark:text-white">Social Links:</span> {{ data_get($profile->social_profiles, 'text', 'N/A') }}</p>
                                <p><span class="font-semibold text-gray-900 dark:text-white">Terms Accepted:</span> {{ $profile->terms_accepted_at ? core()->formatDate($profile->terms_accepted_at, 'd M Y H:i') : 'N/A' }}</p>
                                <p><span class="font-semibold text-gray-900 dark:text-white">Submitted:</span> {{ $profile->created_at ? core()->formatDate($profile->created_at, 'd M Y H:i') : 'N/A' }}</p>
                                <p><span class="font-semibold text-gray-900 dark:text-white">Source:</span> {{ $applicationSource }}</p>
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-white">Promotion Strategy</p>
                                    <p class="mt-1">{{ $profile->application_note ?: 'No application note provided.' }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="affiliate-profile-card">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Affiliate Settings</h3>
                            <div class="mt-4 grid gap-2 text-sm text-gray-600 dark:text-gray-300">
                                <p><span class="font-semibold text-gray-900 dark:text-white">Commission Rule:</span> {{ $settings['commission_rule'] }}</p>
                                <p><span class="font-semibold text-gray-900 dark:text-white">Payout Method:</span> {{ $profile->payout_method ? str($profile->payout_method)->replace('_', ' ')->title() : 'Not set' }}</p>
                                <p><span class="font-semibold text-gray-900 dark:text-white">Payout Account:</span> {{ $profile->payout_reference ?: 'Not set' }}</p>
                                <p><span class="font-semibold text-gray-900 dark:text-white">Cookie Window:</span> {{ $settings['cookie_window_days'] }} days</p>
                                <p><span class="font-semibold text-gray-900 dark:text-white">Internal Metadata:</span> {{ data_get($profile->meta, 'created_by_admin_id') ? 'Admin-created' : 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section
                    id="affiliate-tab-panel-activity"
                    data-affiliate-tab-panel="activity"
                    role="tabpanel"
                    aria-labelledby="affiliate-tab-trigger-activity"
                    class="{{ $activeTab === 'activity' ? '' : 'hidden' }}"
                >
                    <div class="affiliate-profile-card">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                            Activity Log
                        </h3>

                        <div class="mt-5 grid gap-4">
                            @forelse ($dashboard['activity'] as $entry)
                                <div class="flex gap-4">
                                    <div class="mt-1 h-3 w-3 shrink-0 rounded-full bg-blue-600"></div>
                                    <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $entry['title'] }}</p>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            {{ $entry['timestamp'] ? core()->formatDate($entry['timestamp'], 'd M Y H:i') : 'N/A' }} by {{ $entry['actor'] }}
                                        </p>
                                        @if ($entry['note'])
                                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ $entry['note'] }}</p>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500">No activity recorded yet.</p>
                            @endforelse
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>

    @pushOnce('scripts')
        <script
            type="module"
            src="{{ bagisto_asset('js/chart.js') }}"
        >
        </script>

        <script type="text/x-template" id="v-affiliate-profile-performance-trend-template">
            <canvas
                :id="$.uid + '_chart'"
                class="affiliate-profile-performance-canvas"
            ></canvas>
        </script>

        <style>
            .affiliate-profile-hero-grid {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: 1.25rem;
            }

            .affiliate-profile-hero-card {
                position: relative;
                overflow: hidden;
                border: 1px solid rgb(226 232 240);
                border-radius: 1rem;
                background: rgb(255 255 255);
                padding: 1.25rem;
                box-shadow: 0 1px 2px rgb(15 23 42 / 0.04);
            }

            .dark .affiliate-profile-hero-card {
                border-color: rgb(31 41 55);
                background: rgb(17 24 39);
                box-shadow: 0 1px 2px rgb(0 0 0 / 0.2);
            }

            .affiliate-profile-card-header {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 1rem;
            }

            .affiliate-profile-eyebrow,
            .affiliate-profile-block-label,
            .affiliate-profile-info-item p {
                color: rgb(100 116 139);
                font-size: 0.6875rem;
                font-weight: 700;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }

            .dark .affiliate-profile-eyebrow,
            .dark .affiliate-profile-block-label,
            .dark .affiliate-profile-info-item p {
                color: rgb(148 163 184);
            }

            .affiliate-profile-identity-row {
                display: flex;
                align-items: center;
                gap: 1rem;
                margin-top: 1.5rem;
                padding: 1rem;
                border: 1px solid rgb(226 232 240);
                border-radius: 0.875rem;
                background: transparent;
            }

            .dark .affiliate-profile-identity-row {
                border-color: rgb(31 41 55);
                background: transparent;
            }

            .affiliate-profile-avatar {
                display: flex;
                width: 3.5rem;
                height: 3.5rem;
                flex-shrink: 0;
                align-items: center;
                justify-content: center;
                border-radius: 9999px;
                border: 1px solid rgb(186 230 253);
                background: rgb(239 246 255);
                color: rgb(0 111 161);
                font-size: 1.05rem;
                font-weight: 800;
            }

            .dark .affiliate-profile-avatar {
                border-color: rgb(0 164 239 / 0.4);
                background: rgb(0 164 239 / 0.14);
                color: rgb(141 220 255);
            }

            .affiliate-profile-status-badge {
                display: inline-flex;
                align-items: center;
                border-width: 1px;
                border-radius: 9999px;
                padding: 0.375rem 0.75rem;
                font-size: 0.75rem;
                font-weight: 700;
                box-shadow: 0 1px 2px rgb(15 23 42 / 0.04);
            }

            .affiliate-profile-info-grid {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: 0.75rem;
                margin-top: 1rem;
            }

            .affiliate-profile-info-item {
                min-width: 0;
                border: 1px solid rgb(226 232 240);
                border-radius: 0.75rem;
                background: rgb(255 255 255 / 0.78);
                padding: 0.875rem;
            }

            .dark .affiliate-profile-info-item {
                border-color: rgb(31 41 55);
                background: rgb(3 7 18 / 0.3);
            }

            .affiliate-profile-info-item strong {
                display: block;
                margin-top: 0.35rem;
                color: rgb(15 23 42);
                font-size: 0.875rem;
                font-weight: 700;
                line-height: 1.35;
            }

            .dark .affiliate-profile-info-item strong {
                color: rgb(248 250 252);
            }

            .affiliate-profile-referral-stack {
                display: grid;
                gap: 1rem;
                margin-top: 1.25rem;
            }

            .affiliate-profile-referral-block {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                min-width: 0;
                border: 1px solid rgb(226 232 240);
                border-radius: 0.875rem;
                background: transparent;
                padding: 1rem;
            }

            .dark .affiliate-profile-referral-block {
                border-color: rgb(31 41 55);
                background: transparent;
            }

            .affiliate-profile-code-block {
                border-color: rgb(0 164 239 / 0.22);
                background: transparent;
            }

            .dark .affiliate-profile-code-block {
                border-color: rgb(0 164 239 / 0.32);
                background: transparent;
            }

            .affiliate-profile-code-value {
                margin-top: 0.4rem;
                color: rgb(15 23 42);
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
                font-size: 1.35rem;
                font-weight: 800;
                letter-spacing: 0.04em;
                word-break: break-word;
            }

            .dark .affiliate-profile-code-value {
                color: rgb(248 250 252);
            }

            .affiliate-profile-link-value {
                margin-top: 0.45rem;
                color: rgb(30 41 59);
                font-size: 0.8125rem;
                font-weight: 600;
                line-height: 1.5;
                word-break: break-all;
            }

            .dark .affiliate-profile-link-value {
                color: rgb(226 232 240);
            }

            .affiliate-profile-utility-action {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                white-space: nowrap;
                border: 1px solid rgb(226 232 240);
                border-radius: 0.65rem;
                font-size: 0.8125rem;
                font-weight: 650;
                line-height: 1;
                transition:
                    background-color 0.16s ease,
                    border-color 0.16s ease,
                    color 0.16s ease,
                    box-shadow 0.16s ease;
            }

            .affiliate-profile-utility-action {
                background: rgb(255 255 255 / 0.76);
                color: rgb(30 41 59);
                padding: 0.65rem 0.95rem;
                box-shadow: 0 1px 2px rgb(15 23 42 / 0.035);
            }

            .affiliate-profile-utility-action:hover {
                border-color: rgb(203 213 225);
                background: rgb(248 250 252);
                color: rgb(15 23 42);
                box-shadow: 0 6px 16px rgb(15 23 42 / 0.06);
            }

            .affiliate-profile-utility-action.is-copied {
                border-color: rgb(127 186 0 / 0.32);
                background: rgb(127 186 0 / 0.1);
                color: rgb(77 113 0);
                box-shadow: none;
            }

            .affiliate-profile-utility-action:focus-visible {
                outline: 2px solid rgb(0 164 239 / 0.35);
                outline-offset: 2px;
            }

            .dark .affiliate-profile-utility-action {
                border-color: rgb(51 65 85);
                background: rgb(15 23 42 / 0.62);
                color: rgb(226 232 240);
            }

            .dark .affiliate-profile-utility-action:hover {
                border-color: rgb(71 85 105);
                background: rgb(30 41 59);
                color: rgb(248 250 252);
            }

            .dark .affiliate-profile-utility-action.is-copied {
                border-color: rgb(127 186 0 / 0.44);
                background: rgb(127 186 0 / 0.16);
                color: rgb(183 229 106);
            }

            @media (min-width: 640px) {
                .affiliate-profile-info-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }

            @media (max-width: 640px) {
                .affiliate-profile-card-header,
                .affiliate-profile-referral-block {
                    align-items: stretch;
                    flex-direction: column;
                }

                .affiliate-profile-identity-row {
                    align-items: flex-start;
                    flex-direction: column;
                }
            }

            @media (min-width: 1280px) {
                .affiliate-profile-hero-grid {
                    grid-template-columns: minmax(0, 7fr) minmax(24rem, 5fr);
                }
            }

            .affiliate-profile-kpi-grid {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: 1rem;
            }

            .affiliate-profile-kpi-card {
                border-color: color-mix(in srgb, var(--affiliate-kpi-color) 30%, rgb(229 231 235));
                box-shadow:
                    inset 0 0 0 1px color-mix(in srgb, var(--affiliate-kpi-color) 10%, transparent),
                    0 1px 2px rgb(15 23 42 / 0.04);
            }

            .dark .affiliate-profile-kpi-card {
                border-color: color-mix(in srgb, var(--affiliate-kpi-color) 42%, rgb(31 41 55));
            }

            .affiliate-profile-kpi-accent {
                position: absolute;
                inset: 0 auto 0 0;
                width: 0.38rem;
                background: var(--affiliate-kpi-color);
            }

            .affiliate-profile-kpi-value {
                font-size: 1.5rem;
                font-weight: 600;
                letter-spacing: -0.025em;
                line-height: 1.15 !important;
            }

            .affiliate-profile-kpi-icon {
                color: var(--affiliate-kpi-color) !important;
            }

            @media (min-width: 768px) {
                .affiliate-profile-kpi-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }

            @media (min-width: 1024px) {
                .affiliate-profile-kpi-grid {
                    grid-template-columns: repeat(4, minmax(0, 1fr));
                }
            }

            .affiliate-profile-filter-bar {
                border: 1px solid rgb(229 231 235);
                border-radius: 0.875rem;
                background: rgb(255 255 255);
                padding: 0.85rem;
                box-shadow: 0 1px 2px rgb(15 23 42 / 0.035);
            }

            .dark .affiliate-profile-filter-bar {
                border-color: rgb(31 41 55);
                background: rgb(17 24 39);
            }

            .affiliate-profile-filter-grid {
                display: grid;
                gap: 0.75rem;
            }

            .affiliate-profile-filter-field {
                display: grid;
                gap: 0.35rem;
                min-width: 0;
            }

            .affiliate-profile-filter-field span {
                color: rgb(100 116 139);
                font-size: 0.6875rem;
                font-weight: 700;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }

            .dark .affiliate-profile-filter-field span {
                color: rgb(148 163 184);
            }

            .affiliate-profile-filter-field input,
            .affiliate-profile-filter-field select {
                min-height: 2.5rem;
                width: 100%;
                border: 1px solid rgb(226 232 240);
                border-radius: 0.65rem;
                background: rgb(255 255 255);
                padding: 0.55rem 0.75rem;
                color: rgb(15 23 42);
                font-size: 0.875rem;
            }

            .dark .affiliate-profile-filter-field input,
            .dark .affiliate-profile-filter-field select {
                border-color: rgb(31 41 55);
                background: rgb(15 23 42);
                color: rgb(226 232 240);
            }

            .affiliate-profile-filter-actions {
                display: flex;
                align-items: end;
                gap: 0.625rem;
            }

            .affiliate-profile-filter-actions .secondary-button {
                min-height: 2.5rem;
                color: rgb(0 111 161);
            }

            .affiliate-profile-filter-reset {
                display: inline-flex;
                min-height: 2.5rem;
                align-items: center;
                justify-content: center;
                border-radius: 0.65rem;
                padding: 0 0.75rem;
                color: rgb(100 116 139);
                font-size: 0.875rem;
                font-weight: 650;
            }

            .affiliate-profile-filter-reset:hover {
                background: rgb(248 250 252);
                color: rgb(15 23 42);
            }

            .dark .affiliate-profile-filter-reset {
                color: rgb(148 163 184);
            }

            .dark .affiliate-profile-filter-reset:hover {
                background: rgb(31 41 55);
                color: rgb(248 250 252);
            }

            .affiliate-profile-card {
                border: 1px solid rgb(229 231 235);
                border-radius: 0.875rem;
                background: rgb(255 255 255);
                padding: 1rem;
                box-shadow: 0 1px 2px rgb(15 23 42 / 0.04);
            }

            .dark .affiliate-profile-card {
                border-color: rgb(31 41 55);
                background: rgb(17 24 39);
            }

            .affiliate-profile-main-stack,
            .affiliate-profile-side-stack {
                display: grid;
                align-content: start;
                gap: 1.25rem;
            }

            .affiliate-profile-section-heading {
                display: grid;
                gap: 0.25rem;
            }

            .affiliate-profile-overview-grid,
            .affiliate-profile-content-grid {
                display: grid;
                gap: 1.25rem;
            }

            .affiliate-profile-summary-grid,
            .affiliate-profile-profile-grid {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: 1rem;
            }

            .affiliate-profile-table-card {
                overflow: hidden;
            }

            .affiliate-profile-trend-chart {
                min-height: 17rem;
                max-height: 17rem;
                overflow: hidden;
                border-radius: 0.75rem;
                border: 1px solid rgb(226 232 240);
                background: rgb(248 250 252);
                padding: 1rem;
            }

            .dark .affiliate-profile-trend-chart {
                border-color: rgb(31 41 55);
                background: rgb(3 7 18);
            }

            .affiliate-profile-performance-canvas {
                height: 100% !important;
                width: 100% !important;
            }

            .affiliate-profile-traffic-chart {
                min-height: 17rem;
                max-height: 17rem;
                display: flex;
                align-items: end;
                gap: 0.75rem;
                overflow-x: auto;
                border-radius: 0.75rem;
                border: 1px solid rgb(226 232 240);
                background: rgb(248 250 252);
                padding: 1rem 1rem 0.75rem;
            }

            .dark .affiliate-profile-traffic-chart {
                border-color: rgb(31 41 55);
                background: rgb(3 7 18);
            }

            .affiliate-profile-traffic-column {
                display: grid;
                min-width: 2.75rem;
                flex: 1 0 2.75rem;
                gap: 0.5rem;
            }

            .affiliate-profile-traffic-bar {
                display: flex;
                height: 13rem;
                align-items: end;
            }

            .affiliate-profile-chart-legend {
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
                margin-left: 0;
                padding-left: 0;
                user-select: none;
            }

            .dark .affiliate-profile-chart-legend {
                color: rgb(156 163 175);
            }

            .affiliate-profile-chart-legend-item {
                align-items: center;
                display: inline-flex;
                gap: 0.4rem;
                white-space: nowrap;
            }

            .affiliate-profile-chart-swatch {
                background: var(--legend-color);
                border-radius: 9999px;
                display: inline-flex;
                height: 0.38rem;
                width: 1.15rem;
            }

            .affiliate-profile-traffic-bar span {
                display: block;
                width: 100%;
                min-height: 0.5rem;
                border-radius: 0.4rem 0.4rem 0 0;
                background: rgb(0 164 239);
            }

            @media (min-width: 768px) {
                .affiliate-profile-filter-grid {
                    grid-template-columns: minmax(12rem, 1.2fr) minmax(11rem, 1fr) minmax(9.5rem, 0.8fr) minmax(9.5rem, 0.8fr) auto;
                    align-items: end;
                }

                .affiliate-profile-summary-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }

                .affiliate-profile-profile-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }

            @media (min-width: 1280px) {
                .affiliate-profile-overview-grid,
                .affiliate-profile-content-grid {
                    grid-template-columns: minmax(0, 2fr) minmax(20rem, 1fr);
                }

                .affiliate-profile-summary-grid {
                    grid-template-columns: repeat(4, minmax(0, 1fr));
                }

                .affiliate-profile-profile-grid {
                    grid-template-columns: repeat(3, minmax(0, 1fr));
                }
            }
        </style>

        <script type="module">
            app.component('v-affiliate-profile-performance-trend', {
                template: '#v-affiliate-profile-performance-trend-template',

                props: {
                    labels: {
                        type: Array,
                        default: () => [],
                    },

                    tooltipDates: {
                        type: Array,
                        default: () => [],
                    },

                    datasets: {
                        type: Array,
                        default: () => [],
                    },

                    currencyCode: {
                        type: String,
                        default: 'USD',
                    },
                },

                data() {
                    return {
                        chart: undefined,
                    };
                },

                mounted() {
                    this.prepare();
                },

                beforeUnmount() {
                    this.chart?.destroy();
                },

                methods: {
                    prepare() {
                        this.chart?.destroy();

                        this.chart = new Chart(document.getElementById(this.$.uid + '_chart'), {
                            type: 'line',

                            data: {
                                labels: this.labels,
                                datasets: this.datasets,
                            },

                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
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
                                        enabled: true,
                                        intersect: false,
                                        mode: 'index',
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

                                                return this.tooltipDates[item.dataIndex] || item.label;
                                            },
                                            label: (item) => {
                                                const label = item.dataset.label || '';
                                                const value = Number(item.parsed.y || 0);

                                                if (label === 'Commission') {
                                                    return `${label}: ${this.formatMoney(value)}`;
                                                }

                                                return `${label}: ${this.formatNumber(value)}`;
                                            },
                                        },
                                    },
                                },
                                scales: {
                                    x: {
                                        border: {
                                            display: false,
                                        },
                                        grid: {
                                            color: 'rgba(148, 163, 184, 0.18)',
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
                                    },
                                    count: {
                                        beginAtZero: true,
                                        position: 'left',
                                        border: {
                                            display: false,
                                        },
                                        grid: {
                                            color: 'rgba(148, 163, 184, 0.18)',
                                            drawTicks: false,
                                        },
                                        ticks: {
                                            color: '#64748B',
                                            precision: 0,
                                            padding: 8,
                                        },
                                    },
                                    money: {
                                        beginAtZero: true,
                                        position: 'right',
                                        border: {
                                            display: false,
                                        },
                                        grid: {
                                            drawOnChartArea: false,
                                            drawTicks: false,
                                        },
                                        ticks: {
                                            color: '#64748B',
                                            padding: 8,
                                            callback: (value) => this.formatCompactMoney(value),
                                        },
                                    },
                                },
                            },
                        });
                    },

                    formatNumber(value) {
                        return new Intl.NumberFormat(undefined, {
                            maximumFractionDigits: 0,
                        }).format(value);
                    },

                    formatMoney(value) {
                        try {
                            return new Intl.NumberFormat(undefined, {
                                currency: this.currencyCode || 'USD',
                                currencyDisplay: 'narrowSymbol',
                                maximumFractionDigits: 2,
                                minimumFractionDigits: 2,
                                style: 'currency',
                            }).format(value);
                        } catch (error) {
                            return new Intl.NumberFormat(undefined, {
                                maximumFractionDigits: 2,
                                minimumFractionDigits: 2,
                            }).format(value);
                        }
                    },

                    formatCompactMoney(value) {
                        try {
                            return new Intl.NumberFormat(undefined, {
                                currency: this.currencyCode || 'USD',
                                currencyDisplay: 'narrowSymbol',
                                maximumFractionDigits: 1,
                                notation: 'compact',
                                style: 'currency',
                            }).format(value);
                        } catch (error) {
                            return new Intl.NumberFormat(undefined, {
                                maximumFractionDigits: 1,
                                notation: 'compact',
                            }).format(value);
                        }
                    },
                },
            });
        </script>

        <script>
            (() => {
                const tabKeys = @json(array_keys($tabs));
                const activeTabClasses = @json($tabActiveClass);
                const inactiveTabClasses = @json($tabInactiveClass);

                const classList = (classes) => classes.split(/\s+/).filter(Boolean);

                const resolveInitialTab = (tabsRoot) => {
                    const hashTab = window.location.hash.replace('#', '');

                    if (tabKeys.includes(hashTab)) {
                        return hashTab;
                    }

                    const queryTab = new URLSearchParams(window.location.search).get('tab');

                    if (tabKeys.includes(queryTab)) {
                        return queryTab;
                    }

                    return tabsRoot?.dataset.initialTab || 'overview';
                };

                const setActiveTab = (tab, options = {}) => {
                    if (! tabKeys.includes(tab)) {
                        return;
                    }

                    document.querySelectorAll('[data-affiliate-tab-nav]').forEach((trigger) => {
                        const isActive = trigger.dataset.affiliateTabTrigger === tab;

                        trigger.classList.remove(...classList(isActive ? inactiveTabClasses : activeTabClasses));
                        trigger.classList.add(...classList(isActive ? activeTabClasses : inactiveTabClasses));
                        trigger.setAttribute('aria-selected', isActive ? 'true' : 'false');
                    });

                    document.querySelectorAll('[data-affiliate-tab-panel]').forEach((panel) => {
                        panel.classList.toggle('hidden', panel.dataset.affiliateTabPanel !== tab);
                    });

                    if (options.updateUrl !== false) {
                        const url = new URL(window.location.href);

                        url.searchParams.set('tab', tab);
                        url.hash = '';

                        window.history.replaceState({}, '', url.toString());
                    }

                    if (options.scrollTarget) {
                        document.querySelector(options.scrollTarget)?.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start',
                        });
                    }
                };

                const tabsRoot = document.querySelector('[data-affiliate-profile-tabs]');

                if (tabsRoot) {
                    setActiveTab(resolveInitialTab(tabsRoot), { updateUrl: false });
                }

                document.addEventListener('click', (event) => {
                    const tabTrigger = event.target.closest('[data-affiliate-tab-trigger]');

                    if (tabTrigger) {
                        event.preventDefault();

                        setActiveTab(tabTrigger.dataset.affiliateTabTrigger, {
                            scrollTarget: tabTrigger.dataset.affiliateTabScrollTarget,
                        });

                        return;
                    }

                    const payoutToggle = event.target.closest('[data-affiliate-payout-allocation-toggle]');

                    if (payoutToggle) {
                        event.preventDefault();

                        const row = document.querySelector(`[data-affiliate-payout-allocation-row="${payoutToggle.dataset.affiliatePayoutAllocationToggle}"]`);
                        const isExpanded = payoutToggle.getAttribute('aria-expanded') === 'true';

                        row?.classList.toggle('hidden', isExpanded);
                        payoutToggle.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
                        payoutToggle.textContent = isExpanded ? 'See Allocations' : 'Hide Allocations';

                        return;
                    }

                    const button = event.target.closest('[data-affiliate-copy-value]');

                    if (! button) {
                        return;
                    }

                    const value = button.getAttribute('data-affiliate-copy-value') || '';

                    const original = button.textContent;
                    const copyValue = async () => {
                        if (navigator.clipboard && window.isSecureContext) {
                            await navigator.clipboard.writeText(value);

                            return;
                        }

                        const textarea = document.createElement('textarea');
                        textarea.value = value;
                        textarea.setAttribute('readonly', '');
                        textarea.style.position = 'fixed';
                        textarea.style.top = '-9999px';
                        textarea.style.left = '-9999px';

                        document.body.appendChild(textarea);
                        textarea.select();
                        document.execCommand('copy');
                        textarea.remove();
                    };

                    copyValue()
                        .then(() => {
                            button.textContent = 'Copied';
                            button.classList.add('is-copied');

                            setTimeout(() => {
                                button.textContent = original;
                                button.classList.remove('is-copied');
                            }, 1400);
                        })
                        .catch(() => {
                            button.textContent = 'Copy failed';

                            setTimeout(() => {
                                button.textContent = original;
                            }, 1400);
                        });
                });
            })();
        </script>
    @endPushOnce
</x-admin::layouts>
