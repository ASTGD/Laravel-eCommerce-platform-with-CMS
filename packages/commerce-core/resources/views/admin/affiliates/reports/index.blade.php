<x-admin::layouts>
    <x-slot:title>
        Affiliate Reports
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <div>
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                Affiliate Reports
            </p>

            <p class="mt-1 text-sm text-gray-500 dark:text-gray-300">
                Track clicks, attributed sales, commissions, and payouts from the shared affiliate records.
            </p>
        </div>
    </div>

    @php
        $commissionTotals = $summary['commissions'] ?? [];
        $payoutTotals = $summary['payouts'] ?? [];
        $dailyMax = max(array_merge($series['clicks'] ?? [], $series['orders'] ?? [], [1]));
        $commissionMax = max(array_merge($series['commissions'] ?? [], [1]));
    @endphp

    <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
            <p class="text-sm text-gray-500 dark:text-gray-300">Total Affiliates</p>
            <p class="mt-2 text-2xl font-bold text-gray-800 dark:text-white">{{ $summary['total_affiliates'] }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-300">{{ $summary['active_affiliates'] }} active</p>
        </div>

        <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
            <p class="text-sm text-gray-500 dark:text-gray-300">Pending Applications</p>
            <p class="mt-2 text-2xl font-bold text-gray-800 dark:text-white">{{ $summary['pending_applications'] }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-300">Waiting for admin review</p>
        </div>

        <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
            <p class="text-sm text-gray-500 dark:text-gray-300">Total Clicks</p>
            <p class="mt-2 text-2xl font-bold text-gray-800 dark:text-white">{{ $summary['total_clicks'] }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-300">Tracked for reporting only</p>
        </div>

        <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
            <p class="text-sm text-gray-500 dark:text-gray-300">Attributed Orders</p>
            <p class="mt-2 text-2xl font-bold text-gray-800 dark:text-white">{{ $summary['attributed_orders'] }}</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-300">
                Sales:
                @include('commerce-core::admin.affiliates.partials.money', ['amount' => $summary['attributed_sales_total']])
            </p>
        </div>
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-2">
        <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
            <p class="text-base font-semibold text-gray-800 dark:text-white">
                Commission Totals
            </p>

            <div class="mt-4 grid gap-3 text-sm text-gray-600 dark:text-gray-300 md:grid-cols-2">
                <p><span class="font-semibold text-gray-800 dark:text-white">Pending:</span> @include('commerce-core::admin.affiliates.partials.money', ['amount' => $commissionTotals[\Platform\CommerceCore\Models\AffiliateCommission::STATUS_PENDING] ?? 0])</p>
                <p><span class="font-semibold text-gray-800 dark:text-white">Approved:</span> @include('commerce-core::admin.affiliates.partials.money', ['amount' => $commissionTotals[\Platform\CommerceCore\Models\AffiliateCommission::STATUS_APPROVED] ?? 0])</p>
                <p><span class="font-semibold text-gray-800 dark:text-white">Paid:</span> @include('commerce-core::admin.affiliates.partials.money', ['amount' => $commissionTotals[\Platform\CommerceCore\Models\AffiliateCommission::STATUS_PAID] ?? 0])</p>
                <p><span class="font-semibold text-gray-800 dark:text-white">Reversed:</span> @include('commerce-core::admin.affiliates.partials.money', ['amount' => $commissionTotals[\Platform\CommerceCore\Models\AffiliateCommission::STATUS_REVERSED] ?? 0])</p>
            </div>
        </div>

        <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
            <p class="text-base font-semibold text-gray-800 dark:text-white">
                Payout Totals
            </p>

            <div class="mt-4 grid gap-3 text-sm text-gray-600 dark:text-gray-300 md:grid-cols-2">
                <p><span class="font-semibold text-gray-800 dark:text-white">Requested:</span> @include('commerce-core::admin.affiliates.partials.money', ['amount' => $payoutTotals[\Platform\CommerceCore\Models\AffiliatePayout::STATUS_REQUESTED] ?? 0])</p>
                <p><span class="font-semibold text-gray-800 dark:text-white">Approved:</span> @include('commerce-core::admin.affiliates.partials.money', ['amount' => $payoutTotals[\Platform\CommerceCore\Models\AffiliatePayout::STATUS_APPROVED] ?? 0])</p>
                <p><span class="font-semibold text-gray-800 dark:text-white">Paid:</span> @include('commerce-core::admin.affiliates.partials.money', ['amount' => $payoutTotals[\Platform\CommerceCore\Models\AffiliatePayout::STATUS_PAID] ?? 0])</p>
                <p><span class="font-semibold text-gray-800 dark:text-white">Rejected:</span> @include('commerce-core::admin.affiliates.partials.money', ['amount' => $payoutTotals[\Platform\CommerceCore\Models\AffiliatePayout::STATUS_REJECTED] ?? 0])</p>
            </div>
        </div>
    </div>

    <div class="mt-4 box-shadow rounded bg-white p-4 dark:bg-gray-900">
        <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
            <div>
                <p class="text-base font-semibold text-gray-800 dark:text-white">
                    Statistics Graph
                </p>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-300">
                    Last {{ count($series['labels'] ?? []) }} days of traffic, attributed orders, and commission value.
                </p>
            </div>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-gray-600 dark:border-gray-800 dark:text-gray-300">
                        <th class="px-3 py-2">Date</th>
                        <th class="px-3 py-2">Clicks</th>
                        <th class="px-3 py-2">Attributed Orders</th>
                        <th class="px-3 py-2">Commissions</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach (($series['labels'] ?? []) as $index => $label)
                        @php
                            $clicks = (int) ($series['clicks'][$index] ?? 0);
                            $orders = (int) ($series['orders'][$index] ?? 0);
                            $commissions = (float) ($series['commissions'][$index] ?? 0);
                        @endphp

                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="px-3 py-3 font-medium text-gray-800 dark:text-white">{{ $label }}</td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="w-10 text-gray-600 dark:text-gray-300">{{ $clicks }}</span>
                                    <span class="h-2 rounded bg-blue-500" style="width: {{ max(6, ($clicks / $dailyMax) * 160) }}px"></span>
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="w-10 text-gray-600 dark:text-gray-300">{{ $orders }}</span>
                                    <span class="h-2 rounded bg-emerald-500" style="width: {{ max(6, ($orders / $dailyMax) * 160) }}px"></span>
                                </div>
                            </td>
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="min-w-24 text-gray-600 dark:text-gray-300">@include('commerce-core::admin.affiliates.partials.money', ['amount' => $commissions])</span>
                                    <span class="h-2 rounded bg-amber-500" style="width: {{ max(6, ($commissions / $commissionMax) * 160) }}px"></span>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4 box-shadow rounded bg-white p-4 dark:bg-gray-900">
        <p class="text-base font-semibold text-gray-800 dark:text-white">
            Top Affiliates
        </p>

        <div class="mt-4 overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-gray-600 dark:border-gray-800 dark:text-gray-300">
                        <th class="px-3 py-2">Affiliate</th>
                        <th class="px-3 py-2">Referral Code</th>
                        <th class="px-3 py-2">Clicks</th>
                        <th class="px-3 py-2">Orders</th>
                        <th class="px-3 py-2">Commission</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($topAffiliates as $profile)
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="px-3 py-3">
                                <a
                                    href="{{ route('admin.affiliates.profiles.show', $profile) }}"
                                    class="font-semibold text-blue-600 hover:underline"
                                >
                                    {{ trim(($profile->customer?->first_name ?? '').' '.($profile->customer?->last_name ?? '')) ?: 'Affiliate #'.$profile->id }}
                                </a>
                            </td>
                            <td class="px-3 py-3 font-medium text-gray-800 dark:text-white">{{ $profile->referral_code }}</td>
                            <td class="px-3 py-3">{{ $profile->clicks_count }}</td>
                            <td class="px-3 py-3">{{ $profile->attributions_count }}</td>
                            <td class="px-3 py-3">@include('commerce-core::admin.affiliates.partials.money', ['amount' => $profile->commission_total ?? 0])</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-6 text-center text-gray-500 dark:text-gray-300">
                                No affiliate performance data yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin::layouts>
