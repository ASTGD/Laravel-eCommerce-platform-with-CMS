<x-admin::layouts>
    <x-slot:title>
        Affiliate Payouts
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <div>
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                Affiliate Payouts
            </p>

            <p class="mt-1 text-sm text-gray-500 dark:text-gray-300">
                Review withdrawal requests and completed payout records.
            </p>
        </div>
    </div>

    <div class="mt-5 rounded bg-white p-4 dark:bg-gray-900">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-2">
                @foreach ($statusOptions as $statusCode => $statusLabel)
                    <a
                        href="{{ route('admin.affiliates.payouts.index', ['status' => $statusCode]) }}"
                        class="{{ $status === $statusCode ? 'primary-button' : 'transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800' }}"
                    >
                        {{ $statusLabel }}
                        <span class="ltr:ml-1 rtl:mr-1">({{ $statusCounts[$statusCode] ?? 0 }})</span>
                    </a>
                @endforeach
            </div>

            <form
                method="GET"
                action="{{ route('admin.affiliates.payouts.index') }}"
                class="flex min-w-[320px] items-center gap-2 max-sm:min-w-full"
            >
                <input
                    type="hidden"
                    name="status"
                    value="{{ $status }}"
                >

                <input
                    type="search"
                    name="search"
                    value="{{ $search }}"
                    class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                    placeholder="Search affiliate, email, code, reference"
                >

                <button
                    type="submit"
                    class="secondary-button"
                >
                    Search
                </button>
            </form>
        </div>

        <div class="mt-5 overflow-x-auto">
            <table class="w-full min-w-[1120px] text-left">
                <thead>
                    <tr class="border-b border-gray-200 text-sm text-gray-600 dark:border-gray-800 dark:text-gray-300">
                        <th class="px-4 py-3">Affiliate</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Amount</th>
                        <th class="px-4 py-3">Method</th>
                        <th class="px-4 py-3">Reference</th>
                        <th class="px-4 py-3">Account Details</th>
                        <th class="px-4 py-3">Requested</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($payouts as $payout)
                        <tr class="border-b border-gray-100 text-sm dark:border-gray-800">
                            <td class="px-4 py-4">
                                <a
                                    href="{{ route('admin.affiliates.profiles.show', $payout->affiliateProfile) }}"
                                    class="font-semibold text-blue-600 hover:underline"
                                >
                                    {{ trim(($payout->affiliateProfile?->customer?->first_name ?? '').' '.($payout->affiliateProfile?->customer?->last_name ?? '')) ?: 'Affiliate #'.$payout->affiliate_profile_id }}
                                </a>

                                <p class="mt-1 text-gray-500 dark:text-gray-300">
                                    {{ $payout->affiliateProfile?->referral_code }}
                                </p>
                            </td>

                            <td class="px-4 py-4">
                                {{ $payout->status_label }}
                            </td>

                            <td class="px-4 py-4 font-medium text-gray-800 dark:text-white">
                                @include('commerce-core::admin.affiliates.partials.money', ['amount' => $payout->amount, 'currency' => $payout->currency])
                            </td>

                            <td class="px-4 py-4">
                                {{ $payout->payout_method ? str($payout->payout_method)->replace('_', ' ')->title()->value() : 'N/A' }}
                            </td>

                            <td class="px-4 py-4">
                                {{ $payout->payout_reference ?: 'N/A' }}
                            </td>

                            <td class="px-4 py-4">
                                {{ data_get($payout->meta, 'payout_account_details') ?: 'N/A' }}
                            </td>

                            <td class="px-4 py-4">
                                {{ $payout->requested_at ? core()->formatDate($payout->requested_at, 'd M Y H:i') : 'Manual record' }}
                            </td>

                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-2">
                                    @if ($payout->status === \Platform\CommerceCore\Models\AffiliatePayout::STATUS_REQUESTED && bouncer()->hasPermission('affiliates.payouts.manage'))
                                        <form method="POST" action="{{ route('admin.affiliates.payouts.approve', $payout) }}">
                                            @csrf

                                            <button type="submit" class="secondary-button">
                                                Approve
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.affiliates.payouts.reject', $payout) }}">
                                            @csrf

                                            <input type="hidden" name="reason" value="Rejected by admin.">

                                            <button type="submit" class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800">
                                                Reject
                                            </button>
                                        </form>
                                    @endif

                                    @if (in_array($payout->status, [\Platform\CommerceCore\Models\AffiliatePayout::STATUS_REQUESTED, \Platform\CommerceCore\Models\AffiliatePayout::STATUS_APPROVED], true) && bouncer()->hasPermission('affiliates.payouts.manage'))
                                        <form method="POST" action="{{ route('admin.affiliates.payouts.mark-paid', $payout) }}">
                                            @csrf

                                            <button type="submit" class="primary-button">
                                                Mark Paid
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="8"
                                class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-300"
                            >
                                No payouts found for this status.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-5">
            {{ $payouts->links() }}
        </div>
    </div>
</x-admin::layouts>
