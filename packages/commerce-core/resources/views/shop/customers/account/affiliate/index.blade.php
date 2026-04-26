<x-shop::layouts.account>
    <x-slot:title>
        Affiliate
    </x-slot>

    @if (core()->getConfigData('general.general.breadcrumbs.shop'))
        @section('breadcrumbs')
            <x-shop::breadcrumbs name="affiliate" />
        @endSection
    @endif

    <div class="max-md:hidden">
        <x-shop::layouts.account.navigation />
    </div>

    <div class="flex-auto mx-4 max-md:mx-6 max-sm:mx-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <a
                    class="grid md:hidden"
                    href="{{ route('shop.customers.account.index') }}"
                >
                    <span class="text-2xl icon-arrow-left rtl:icon-arrow-right"></span>
                </a>

                <div class="ltr:ml-2.5 md:ltr:ml-0 rtl:mr-2.5 md:rtl:mr-0">
                    <h2 class="text-2xl font-medium max-md:text-xl max-sm:text-base">
                        Affiliate
                    </h2>

                    <p class="mt-1 text-sm text-zinc-500">
                        Apply to promote the store and earn order-based commissions after approval.
                    </p>
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if (session('warning'))
            <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">
                {{ session('warning') }}
            </div>
        @endif

        <div class="mt-8 grid gap-6">
            @if ($portalState === \Platform\CommerceCore\Services\Affiliates\AffiliateProfileService::PORTAL_STATE_NO_PROFILE)
                @include('commerce-core::shop.customers.account.affiliate.partials.application-form', [
                    'profile' => null,
                    'payoutMethods' => $payoutMethods,
                    'termsText' => $termsText,
                    'submitLabel' => 'Submit Application',
                ])
            @elseif ($portalState === \Platform\CommerceCore\Services\Affiliates\AffiliateProfileService::PORTAL_STATE_PENDING)
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6">
                    <p class="text-lg font-medium text-amber-950">
                        Application under review
                    </p>

                    <p class="mt-2 text-sm leading-6 text-amber-800">
                        Your affiliate application is pending admin review. The full Affiliate portal will become available only after approval.
                    </p>
                </div>
            @elseif ($portalState === \Platform\CommerceCore\Services\Affiliates\AffiliateProfileService::PORTAL_STATE_REJECTED)
                <div class="rounded-2xl border border-red-200 bg-red-50 p-6">
                    <p class="text-lg font-medium text-red-950">
                        Application was not approved
                    </p>

                    <p class="mt-2 text-sm leading-6 text-red-800">
                        @if ($profile?->rejection_reason)
                            {{ $profile->rejection_reason }}
                        @else
                            You can update the details below and submit the application again.
                        @endif
                    </p>
                </div>

                @include('commerce-core::shop.customers.account.affiliate.partials.application-form', [
                    'profile' => $profile,
                    'payoutMethods' => $payoutMethods,
                    'termsText' => $termsText,
                    'submitLabel' => 'Resubmit Application',
                ])
            @elseif ($portalState === \Platform\CommerceCore\Services\Affiliates\AffiliateProfileService::PORTAL_STATE_SUSPENDED)
                <div class="rounded-2xl border border-zinc-200 bg-zinc-50 p-6">
                    <p class="text-lg font-medium text-zinc-950">
                        Affiliate account suspended
                    </p>

                    <p class="mt-2 text-sm leading-6 text-zinc-600">
                        Your affiliate account is currently suspended. Contact support if you need help with this status.
                    </p>
                </div>
            @elseif ($portalState === \Platform\CommerceCore\Services\Affiliates\AffiliateProfileService::PORTAL_STATE_ACTIVE)
                @include('commerce-core::shop.customers.account.affiliate.partials.dashboard', [
                    'profile' => $profile,
                    'dashboard' => $dashboard,
                    'payoutMethods' => $payoutMethods,
                    'referralBuilder' => $referralBuilder,
                ])
            @endif
        </div>
    </div>
</x-shop::layouts.account>
