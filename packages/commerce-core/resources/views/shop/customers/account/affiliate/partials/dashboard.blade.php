@php
    $currency = $dashboard['currency'] ?? core()->getBaseCurrencyCode();
    $formatMoney = static fn ($amount) => core()->formatPrice((float) $amount, $currency);
    $availableBalance = (float) data_get($dashboard, 'balance.available_balance', 0);
    $minimumPayoutAmount = (float) data_get($dashboard, 'minimum_payout_amount', 0);
    $canRequestPayout = $availableBalance >= $minimumPayoutAmount;
@endphp

<div class="grid gap-6">
    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6">
        <div class="flex flex-wrap items-start justify-between gap-5">
            <div class="max-w-2xl">
                <p class="text-xl font-semibold text-emerald-950">
                    Affiliate Dashboard
                </p>

                <p class="mt-2 text-sm leading-6 text-emerald-800">
                    Share your referral link, track referred orders, and request payouts from one shared affiliate account.
                </p>
            </div>

            <div class="rounded-xl border border-emerald-200 bg-white px-4 py-3">
                <p class="text-xs font-medium uppercase text-emerald-700">
                    Referral Code
                </p>

                <p class="mt-1 text-lg font-semibold text-emerald-950">
                    {{ data_get($dashboard, 'referral.code') }}
                </p>
            </div>
        </div>

        <div class="mt-5 grid gap-2">
            <label
                for="affiliate_referral_url"
                class="text-sm font-medium text-emerald-950"
            >
                Referral link
            </label>

            <input
                id="affiliate_referral_url"
                type="text"
                readonly
                value="{{ data_get($dashboard, 'referral.url') }}"
                class="w-full rounded-xl border border-emerald-200 bg-white px-4 py-3 text-sm text-emerald-950"
            >

            <p class="text-xs text-emerald-700">
                Share this link with your audience. Commissions are created only for attributed orders, not for clicks.
            </p>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-zinc-200 bg-white p-5">
            <p class="text-sm text-zinc-500">Total clicks</p>
            <p class="mt-2 text-2xl font-semibold text-black">{{ data_get($dashboard, 'traffic.total_clicks', 0) }}</p>
            <p class="mt-1 text-xs text-zinc-500">{{ data_get($dashboard, 'traffic.clicks_this_month', 0) }} this month</p>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-5">
            <p class="text-sm text-zinc-500">Attributed orders</p>
            <p class="mt-2 text-2xl font-semibold text-black">{{ data_get($dashboard, 'sales.attributed_orders', 0) }}</p>
            <p class="mt-1 text-xs text-zinc-500">{{ $formatMoney(data_get($dashboard, 'sales.attributed_sales_total', 0)) }} referred sales</p>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-5">
            <p class="text-sm text-zinc-500">Pending commission</p>
            <p class="mt-2 text-2xl font-semibold text-black">{{ $formatMoney(data_get($dashboard, 'commissions.pending', 0)) }}</p>
            <p class="mt-1 text-xs text-zinc-500">Waiting for admin approval</p>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-5">
            <p class="text-sm text-zinc-500">Available payout</p>
            <p class="mt-2 text-2xl font-semibold text-black">{{ $formatMoney($availableBalance) }}</p>
            <p class="mt-1 text-xs text-zinc-500">Minimum {{ $formatMoney($minimumPayoutAmount) }}</p>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
        <div class="rounded-2xl border border-zinc-200 bg-white p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-lg font-medium text-black">
                        Commission summary
                    </p>

                    <p class="mt-1 text-sm text-zinc-500">
                        These totals come from the shared affiliate commission ledger.
                    </p>
                </div>

                <div class="text-right">
                    <p class="text-xs uppercase text-zinc-500">Total earned</p>
                    <p class="mt-1 text-lg font-semibold text-black">{{ $formatMoney(data_get($dashboard, 'commissions.total_earned', 0)) }}</p>
                </div>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                <div class="rounded-xl bg-zinc-50 p-4">
                    <p class="text-xs uppercase text-zinc-500">Approved</p>
                    <p class="mt-1 text-base font-semibold text-black">{{ $formatMoney(data_get($dashboard, 'commissions.approved', 0)) }}</p>
                </div>

                <div class="rounded-xl bg-zinc-50 p-4">
                    <p class="text-xs uppercase text-zinc-500">Paid</p>
                    <p class="mt-1 text-base font-semibold text-black">{{ $formatMoney(data_get($dashboard, 'commissions.paid', 0)) }}</p>
                </div>

                <div class="rounded-xl bg-zinc-50 p-4">
                    <p class="text-xs uppercase text-zinc-500">Reserved for payout</p>
                    <p class="mt-1 text-base font-semibold text-black">{{ $formatMoney(data_get($dashboard, 'balance.reserved_payouts', 0)) }}</p>
                </div>

                <div class="rounded-xl bg-zinc-50 p-4">
                    <p class="text-xs uppercase text-zinc-500">Reversed</p>
                    <p class="mt-1 text-base font-semibold text-black">{{ $formatMoney(data_get($dashboard, 'commissions.reversed', 0)) }}</p>
                </div>
            </div>
        </div>

        <form
            method="POST"
            action="{{ route('shop.customers.account.affiliate.withdrawals.store') }}"
            class="rounded-2xl border border-zinc-200 bg-white p-6"
        >
            @csrf

            <div>
                <p class="text-lg font-medium text-black">
                    Request payout
                </p>

                <p class="mt-1 text-sm leading-6 text-zinc-500">
                    Submit a withdrawal request from your available approved commission balance.
                </p>
            </div>

            @unless ($canRequestPayout)
                <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    You can request a payout after your available balance reaches {{ $formatMoney($minimumPayoutAmount) }}.
                </div>
            @endunless

            <div class="mt-5 grid gap-4">
                <div class="grid gap-2">
                    <label
                        for="amount"
                        class="text-sm font-medium text-zinc-800"
                    >
                        Amount <span class="text-red-600">*</span>
                    </label>

                    <input
                        id="amount"
                        name="amount"
                        type="number"
                        min="0"
                        step="0.01"
                        value="{{ old('amount') }}"
                        class="rounded-xl border border-zinc-200 px-4 py-3 text-sm text-black outline-none transition hover:border-zinc-300 focus:border-navyBlue"
                        placeholder="{{ number_format($availableBalance, 2, '.', '') }}"
                    >

                    @error('amount')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-2">
                    <label
                        for="withdrawal_payout_method"
                        class="text-sm font-medium text-zinc-800"
                    >
                        Payout method
                    </label>

                    <select
                        id="withdrawal_payout_method"
                        name="payout_method"
                        class="rounded-xl border border-zinc-200 px-4 py-3 text-sm text-black outline-none transition hover:border-zinc-300 focus:border-navyBlue"
                    >
                        <option value="">Use saved preference</option>

                        @foreach ($payoutMethods as $methodCode => $methodLabel)
                            <option
                                value="{{ $methodCode }}"
                                @selected(old('payout_method', $profile?->payout_method) === $methodCode)
                            >
                                {{ $methodLabel }}
                            </option>
                        @endforeach
                    </select>

                    @error('payout_method')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-2">
                    <label
                        for="withdrawal_payout_reference"
                        class="text-sm font-medium text-zinc-800"
                    >
                        Payout account details
                    </label>

                    <input
                        id="withdrawal_payout_reference"
                        name="payout_reference"
                        type="text"
                        value="{{ old('payout_reference', $profile?->payout_reference) }}"
                        class="rounded-xl border border-zinc-200 px-4 py-3 text-sm text-black outline-none transition hover:border-zinc-300 focus:border-navyBlue"
                        placeholder="Bank, wallet, or transfer account details"
                    >

                    @error('payout_reference')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid gap-2">
                    <label
                        for="notes"
                        class="text-sm font-medium text-zinc-800"
                    >
                        Note
                    </label>

                    <textarea
                        id="notes"
                        name="notes"
                        rows="3"
                        class="rounded-xl border border-zinc-200 px-4 py-3 text-sm text-black outline-none transition hover:border-zinc-300 focus:border-navyBlue"
                        placeholder="Optional payout note for the admin team"
                    >{{ old('notes') }}</textarea>

                    @error('notes')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-5 flex justify-end">
                <button
                    type="submit"
                    class="primary-button rounded-xl px-6 py-3 text-sm font-medium disabled:cursor-not-allowed disabled:opacity-60"
                    @disabled(! $canRequestPayout)
                >
                    Request Withdrawal
                </button>
            </div>
        </form>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <div class="rounded-2xl border border-zinc-200 bg-white p-6">
            <p class="text-lg font-medium text-black">
                Payout history
            </p>

            <div class="mt-5 overflow-x-auto">
                <table class="w-full min-w-[680px] text-left text-sm">
                    <thead class="border-b border-zinc-200 text-xs uppercase text-zinc-500">
                        <tr>
                            <th class="py-3 pr-4">Reference</th>
                            <th class="py-3 pr-4">Amount</th>
                            <th class="py-3 pr-4">Status</th>
                            <th class="py-3 pr-4">Method</th>
                            <th class="py-3 pr-4">Requested</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-zinc-100">
                        @forelse ($dashboard['payouts'] as $payout)
                            <tr>
                                <td class="py-3 pr-4 font-medium text-black">{{ $payout->payout_reference }}</td>
                                <td class="py-3 pr-4 text-zinc-700">{{ $formatMoney($payout->amount) }}</td>
                                <td class="py-3 pr-4 text-zinc-700">{{ $payout->status_label }}</td>
                                <td class="py-3 pr-4 text-zinc-700">
                                    {{ $payout->payout_method ? str($payout->payout_method)->replace('_', ' ')->title() : 'Saved preference' }}

                                    @if (data_get($payout->meta, 'payout_account_details'))
                                        <p class="mt-1 text-xs text-zinc-500">
                                            {{ data_get($payout->meta, 'payout_account_details') }}
                                        </p>
                                    @endif
                                </td>
                                <td class="py-3 pr-4 text-zinc-500">{{ $payout->requested_at ? core()->formatDate($payout->requested_at, 'd M Y') : '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="5"
                                    class="py-6 text-center text-zinc-500"
                                >
                                    No payout requests yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-6">
            <p class="text-lg font-medium text-black">
                Recent commissions
            </p>

            <div class="mt-5 overflow-x-auto">
                <table class="w-full min-w-[520px] text-left text-sm">
                    <thead class="border-b border-zinc-200 text-xs uppercase text-zinc-500">
                        <tr>
                            <th class="py-3 pr-4">Order</th>
                            <th class="py-3 pr-4">Order amount</th>
                            <th class="py-3 pr-4">Commission</th>
                            <th class="py-3 pr-4">Status</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-zinc-100">
                        @forelse ($dashboard['recent_commissions'] as $commission)
                            <tr>
                                <td class="py-3 pr-4 font-medium text-black">#{{ $commission->order?->increment_id ?? $commission->order_id }}</td>
                                <td class="py-3 pr-4 text-zinc-700">{{ $formatMoney($commission->order_amount) }}</td>
                                <td class="py-3 pr-4 text-zinc-700">{{ $formatMoney($commission->commission_amount) }}</td>
                                <td class="py-3 pr-4 text-zinc-700">{{ $commission->status_label }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td
                                    colspan="4"
                                    class="py-6 text-center text-zinc-500"
                                >
                                    No commissions yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
