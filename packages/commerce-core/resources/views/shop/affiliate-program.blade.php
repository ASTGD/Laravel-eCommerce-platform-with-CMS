@php
    $commission = $settings['default_commission'] ?? ['type' => 'percentage', 'value' => 10];
    $commissionText = ($commission['type'] ?? 'percentage') === 'percentage'
        ? rtrim(rtrim(number_format((float) ($commission['value'] ?? 0), 2), '0'), '.').'%'
        : core()->formatPrice((float) ($commission['value'] ?? 0), core()->getBaseCurrencyCode());
@endphp

<x-shop::layouts>
    <x-slot:title>
        Affiliate Program
    </x-slot>

    <div class="mx-auto max-w-6xl px-4 py-12 max-md:py-8">
        <section class="overflow-hidden rounded-3xl border border-zinc-200 bg-white">
            <div class="grid gap-8 p-8 md:grid-cols-[1.2fr_0.8fr] md:p-10">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-navyBlue">
                        Affiliate Program
                    </p>

                    <h1 class="mt-3 max-w-3xl text-4xl font-semibold leading-tight text-black max-md:text-3xl">
                        Promote products you trust and earn order-based commission.
                    </h1>

                    <p class="mt-4 max-w-2xl text-base leading-7 text-zinc-600">
                        Apply from your customer account. After approval, you will get a referral code, tracked links, commission reporting, and payout requests in one customer portal.
                    </p>

                    <div class="mt-7 flex flex-wrap gap-3">
                        @guest('customer')
                            <a
                                href="{{ route('shop.customer.session.index', ['redirect_to' => 'account']) }}"
                                class="inline-flex items-center justify-center rounded-xl bg-navyBlue px-6 py-3 text-sm font-medium text-white transition hover:opacity-90"
                            >
                                Login to Apply
                            </a>

                            <a
                                href="{{ route('shop.customers.register.index') }}"
                                class="inline-flex items-center justify-center rounded-xl border border-zinc-300 px-6 py-3 text-sm font-medium text-black transition hover:border-zinc-400"
                            >
                                Register
                            </a>
                        @else
                            <a
                                href="{{ route('shop.customers.account.affiliate.index') }}"
                                class="inline-flex items-center justify-center rounded-xl bg-navyBlue px-6 py-3 text-sm font-medium text-white transition hover:opacity-90"
                            >
                                Go to Affiliate Application
                            </a>
                        @endguest
                    </div>
                </div>

                <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-6">
                    <p class="text-lg font-semibold text-black">
                        Program basics
                    </p>

                    <div class="mt-5 grid gap-4 text-sm text-zinc-700">
                        <div class="rounded-xl bg-white p-4">
                            <p class="font-semibold text-black">Commission</p>
                            <p class="mt-1">{{ $commissionText }} default order commission after eligible attribution.</p>
                        </div>

                        <div class="rounded-xl bg-white p-4">
                            <p class="font-semibold text-black">Attribution window</p>
                            <p class="mt-1">{{ $settings['cookie_window_days'] ?? 30 }} days from the visitor click.</p>
                        </div>

                        <div class="rounded-xl bg-white p-4">
                            <p class="font-semibold text-black">Approval</p>
                            <p class="mt-1">
                                {{ ($settings['approval_required'] ?? true) ? 'Applications are reviewed before dashboard access.' : 'Eligible applications become active automatically.' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mt-8 grid gap-4 md:grid-cols-3">
            <div class="rounded-2xl border border-zinc-200 bg-white p-6">
                <p class="text-lg font-medium text-black">
                    Apply with your customer account
                </p>

                <p class="mt-2 text-sm leading-6 text-zinc-500">
                    One customer account maps to one affiliate profile. There is no separate affiliate login.
                </p>
            </div>

            <div class="rounded-2xl border border-zinc-200 bg-white p-6">
                <p class="text-lg font-medium text-black">
                    Share tracked links
                </p>

                <p class="mt-2 text-sm leading-6 text-zinc-500">
                    Approved affiliates get a stable referral code and can build simple tracked links for internal store pages.
                </p>
            </div>

            <div class="rounded-2xl border border-zinc-200 bg-white p-6">
                <p class="text-lg font-medium text-black">
                    Request payouts
                </p>

                <p class="mt-2 text-sm leading-6 text-zinc-500">
                    Commissions, withdrawal requests, and payout history are visible from the customer Affiliate portal.
                </p>
            </div>
        </section>
    </div>
</x-shop::layouts>
