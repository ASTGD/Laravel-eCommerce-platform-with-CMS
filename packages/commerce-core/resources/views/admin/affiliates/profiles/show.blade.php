<x-admin::layouts>
    <x-slot:title>
        Affiliate Profile #{{ $profile->id }}
    </x-slot>

    <div class="flex items-start justify-between gap-4 max-sm:flex-wrap">
        <div>
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                Affiliate Profile
            </p>

            <p class="mt-1 text-sm text-gray-500 dark:text-gray-300">
                {{ trim(($profile->customer?->first_name ?? '').' '.($profile->customer?->last_name ?? '')) ?: 'Customer #'.$profile->customer_id }}
                · {{ $profile->status_label }}
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a
                href="{{ route('admin.affiliates.profiles.index', ['status' => $profile->status]) }}"
                class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
            >
                Back
            </a>

            @if ($profile->status === \Platform\CommerceCore\Models\AffiliateProfile::STATUS_PENDING && bouncer()->hasPermission('affiliates.profiles.approve'))
                <form method="POST" action="{{ route('admin.affiliates.profiles.approve', $profile) }}">
                    @csrf

                    <button type="submit" class="primary-button">
                        Approve
                    </button>
                </form>
            @endif

            @if (in_array($profile->status, [\Platform\CommerceCore\Models\AffiliateProfile::STATUS_PENDING, \Platform\CommerceCore\Models\AffiliateProfile::STATUS_ACTIVE], true) && bouncer()->hasPermission('affiliates.profiles.reject'))
                <form method="POST" action="{{ route('admin.affiliates.profiles.reject', $profile) }}">
                    @csrf

                    <input type="hidden" name="reason" value="Rejected by admin.">

                    <button type="submit" class="secondary-button">
                        Reject
                    </button>
                </form>
            @endif

            @if ($profile->status === \Platform\CommerceCore\Models\AffiliateProfile::STATUS_ACTIVE && bouncer()->hasPermission('affiliates.profiles.suspend'))
                <form method="POST" action="{{ route('admin.affiliates.profiles.suspend', $profile) }}">
                    @csrf

                    <input type="hidden" name="reason" value="Suspended by admin.">

                    <button type="submit" class="secondary-button">
                        Suspend
                    </button>
                </form>
            @endif

            @if (in_array($profile->status, [\Platform\CommerceCore\Models\AffiliateProfile::STATUS_SUSPENDED, \Platform\CommerceCore\Models\AffiliateProfile::STATUS_REJECTED], true) && bouncer()->hasPermission('affiliates.profiles.reactivate'))
                <form method="POST" action="{{ route('admin.affiliates.profiles.reactivate', $profile) }}">
                    @csrf

                    <button type="submit" class="primary-button">
                        Reactivate
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if (session('success'))
        <div class="mt-5 rounded border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="mt-5 grid gap-4 lg:grid-cols-[2fr_1fr]">
        <div class="grid gap-4">
            <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                <p class="text-base font-semibold text-gray-800 dark:text-white">
                    Customer and Application
                </p>

                <div class="mt-4 grid gap-3 text-sm text-gray-600 dark:text-gray-300 md:grid-cols-2">
                    <p><span class="font-semibold text-gray-800 dark:text-white">Name:</span> {{ trim(($profile->customer?->first_name ?? '').' '.($profile->customer?->last_name ?? '')) ?: 'N/A' }}</p>
                    <p><span class="font-semibold text-gray-800 dark:text-white">Email:</span> {{ $profile->customer?->email ?? 'N/A' }}</p>
                    <p><span class="font-semibold text-gray-800 dark:text-white">Phone:</span> {{ $profile->customer?->phone ?? 'N/A' }}</p>
                    <p><span class="font-semibold text-gray-800 dark:text-white">Website:</span> {{ $profile->website_url ?: 'N/A' }}</p>
                    <p><span class="font-semibold text-gray-800 dark:text-white">Applied:</span> {{ $profile->created_at ? core()->formatDate($profile->created_at, 'd M Y H:i') : 'N/A' }}</p>
                    <p><span class="font-semibold text-gray-800 dark:text-white">Terms Accepted:</span> {{ $profile->terms_accepted_at ? core()->formatDate($profile->terms_accepted_at, 'd M Y H:i') : 'N/A' }}</p>
                </div>

                <div class="mt-4 grid gap-3 text-sm text-gray-600 dark:text-gray-300">
                    <div>
                        <p class="font-semibold text-gray-800 dark:text-white">Application Note</p>
                        <p class="mt-1">{{ $profile->application_note ?: 'No application note provided.' }}</p>
                    </div>

                    <div>
                        <p class="font-semibold text-gray-800 dark:text-white">Social Profiles</p>
                        <p class="mt-1">{{ data_get($profile->social_profiles, 'text', 'N/A') }}</p>
                    </div>
                </div>
            </div>

            <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                <div class="flex items-start justify-between gap-4 max-sm:flex-wrap">
                    <div>
                        <p class="text-base font-semibold text-gray-800 dark:text-white">
                            Referral
                        </p>

                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-300">
                            The link stays valid while this affiliate is active. Regenerating the code invalidates old referral links.
                        </p>
                    </div>

                    @if (bouncer()->hasPermission('affiliates.profiles.regenerate_referral_code'))
                        <form
                            method="POST"
                            action="{{ route('admin.affiliates.profiles.regenerate-referral-code', $profile) }}"
                            onsubmit="return confirm('Regenerate this referral code? Old referral links will stop creating new attribution.');"
                        >
                            @csrf

                            <button
                                type="submit"
                                class="secondary-button"
                            >
                                Regenerate Referral Code
                            </button>
                        </form>
                    @endif
                </div>

                <div class="mt-4 grid gap-3 text-sm text-gray-600 dark:text-gray-300 md:grid-cols-2">
                    <div class="rounded border border-gray-200 p-3 dark:border-gray-800">
                        <p class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-300">
                            Referral Code
                        </p>

                        <p class="mt-1 font-semibold text-gray-800 dark:text-white">
                            {{ $profile->referral_code }}
                        </p>
                    </div>

                    <div class="rounded border border-gray-200 p-3 dark:border-gray-800">
                        <p class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-300">
                            Referral Link
                        </p>

                        <p class="mt-1 break-all font-medium text-gray-800 dark:text-white">
                            {{ $profile->referral_url }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                <p class="text-base font-semibold text-gray-800 dark:text-white">
                    Recent Commissions
                </p>

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[720px] text-left text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 text-gray-600 dark:border-gray-800 dark:text-gray-300">
                                <th class="px-3 py-2">Order</th>
                                <th class="px-3 py-2">Status</th>
                                <th class="px-3 py-2">Order Amount</th>
                                <th class="px-3 py-2">Commission</th>
                                <th class="px-3 py-2">Created</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($profile->commissions as $commission)
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <td class="px-3 py-3">
                                        {{ $commission->order?->increment_id ? '#'.$commission->order->increment_id : 'Order #'.$commission->order_id }}
                                    </td>
                                    <td class="px-3 py-3">{{ $commission->status_label }}</td>
                                    <td class="px-3 py-3">@include('commerce-core::admin.affiliates.partials.money', ['amount' => $commission->order_amount, 'currency' => $commission->currency])</td>
                                    <td class="px-3 py-3">@include('commerce-core::admin.affiliates.partials.money', ['amount' => $commission->commission_amount, 'currency' => $commission->currency])</td>
                                    <td class="px-3 py-3">{{ core()->formatDate($commission->created_at, 'd M Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-3 py-6 text-center text-gray-500 dark:text-gray-300">
                                        No commissions recorded yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                <p class="text-base font-semibold text-gray-800 dark:text-white">
                    Payout History
                </p>

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[860px] text-left text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 text-gray-600 dark:border-gray-800 dark:text-gray-300">
                                <th class="px-3 py-2">Reference</th>
                                <th class="px-3 py-2">Status</th>
                                <th class="px-3 py-2">Method</th>
                                <th class="px-3 py-2">Account Details</th>
                                <th class="px-3 py-2">Amount</th>
                                <th class="px-3 py-2">Date</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($profile->payouts as $payout)
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <td class="px-3 py-3">{{ $payout->payout_reference ?: 'N/A' }}</td>
                                    <td class="px-3 py-3">{{ $payout->status_label }}</td>
                                    <td class="px-3 py-3">{{ $payout->payout_method ? str($payout->payout_method)->replace('_', ' ')->title() : 'N/A' }}</td>
                                    <td class="px-3 py-3">{{ data_get($payout->meta, 'payout_account_details') ?: 'N/A' }}</td>
                                    <td class="px-3 py-3">@include('commerce-core::admin.affiliates.partials.money', ['amount' => $payout->amount, 'currency' => $payout->currency])</td>
                                    <td class="px-3 py-3">{{ core()->formatDate($payout->created_at, 'd M Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-3 py-6 text-center text-gray-500 dark:text-gray-300">
                                        No payouts recorded yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="grid content-start gap-4">
            <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                <p class="text-base font-semibold text-gray-800 dark:text-white">
                    Summary
                </p>

                <div class="mt-4 grid gap-3 text-sm text-gray-600 dark:text-gray-300">
                    <p><span class="font-semibold text-gray-800 dark:text-white">Clicks:</span> {{ $trafficSummary['clicks'] }} total · {{ $trafficSummary['recent_clicks'] }} last 30 days</p>
                    <p><span class="font-semibold text-gray-800 dark:text-white">Attributed Orders:</span> {{ $salesSummary['attributed_orders'] }}</p>
                    <p><span class="font-semibold text-gray-800 dark:text-white">Pending Commission:</span> @include('commerce-core::admin.affiliates.partials.money', ['amount' => $commissionSummary['pending']])</p>
                    <p><span class="font-semibold text-gray-800 dark:text-white">Approved Commission:</span> @include('commerce-core::admin.affiliates.partials.money', ['amount' => $commissionSummary['approved']])</p>
                    <p><span class="font-semibold text-gray-800 dark:text-white">Paid Payouts:</span> @include('commerce-core::admin.affiliates.partials.money', ['amount' => $payoutSummary['paid']])</p>
                    <p><span class="font-semibold text-gray-800 dark:text-white">Available Balance:</span> @include('commerce-core::admin.affiliates.partials.money', ['amount' => $balance['available_balance']])</p>
                </div>
            </div>

            @if (bouncer()->hasPermission('affiliates.payouts.manage'))
                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="text-base font-semibold text-gray-800 dark:text-white">
                        Add Payout Record
                    </p>

                    <form
                        method="POST"
                        action="{{ route('admin.affiliates.profiles.payouts.store', $profile) }}"
                        class="mt-4 grid gap-3"
                    >
                        @csrf

                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            name="amount"
                            value="{{ old('amount') }}"
                            class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                            placeholder="Amount"
                        >
                        @error('amount') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

                        <select
                            name="payout_method"
                            class="custom-select w-full rounded-md border bg-white px-3 py-2.5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                        >
                            @foreach ($payoutMethods as $methodCode => $methodLabel)
                                <option value="{{ $methodCode }}" @selected(old('payout_method', $profile->payout_method) === $methodCode)>
                                    {{ $methodLabel }}
                                </option>
                            @endforeach
                        </select>
                        @error('payout_method') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

                        <input
                            type="text"
                            name="currency"
                            value="{{ old('currency') }}"
                            class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                            placeholder="Currency, e.g. USD"
                        >
                        @error('currency') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

                        <input
                            type="text"
                            name="payout_reference"
                            value="{{ old('payout_reference') }}"
                            class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                            placeholder="Payment reference"
                        >
                        @error('payout_reference') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

                        <textarea
                            name="admin_notes"
                            rows="3"
                            class="w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                            placeholder="Admin note"
                        >{{ old('admin_notes') }}</textarea>

                        <button type="submit" class="primary-button justify-center">
                            Add Paid Payout
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-admin::layouts>
