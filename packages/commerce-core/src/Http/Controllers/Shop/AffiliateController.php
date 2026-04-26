<?php

namespace Platform\CommerceCore\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Platform\CommerceCore\Http\Requests\Shop\AffiliateApplicationRequest;
use Platform\CommerceCore\Http\Requests\Shop\AffiliateWithdrawalRequest;
use Platform\CommerceCore\Models\AffiliateProfile;
use Platform\CommerceCore\Services\Affiliates\AffiliatePayoutService;
use Platform\CommerceCore\Services\Affiliates\AffiliatePortalService;
use Platform\CommerceCore\Services\Affiliates\AffiliateProfileService;
use Platform\CommerceCore\Services\Affiliates\AffiliateSettingsService;

class AffiliateController extends Controller
{
    public function __construct(
        protected AffiliateProfileService $affiliateProfileService,
        protected AffiliatePortalService $affiliatePortalService,
        protected AffiliatePayoutService $affiliatePayoutService,
        protected AffiliateSettingsService $affiliateSettingsService,
    ) {}

    public function index(): View
    {
        $customer = auth()->guard('customer')->user();
        $profile = $this->affiliateProfileService->profileForCustomer($customer);

        return view('commerce-core::shop.customers.account.affiliate.index', [
            'customer' => $customer,
            'profile' => $profile,
            'portalState' => $this->affiliateProfileService->portalState($customer),
            'dashboard' => $profile?->isActive() ? $this->affiliatePortalService->dashboardFor($profile) : null,
            'payoutMethods' => $this->affiliateSettingsService->payoutMethods(),
            'termsText' => $this->affiliateSettingsService->termsText(),
        ]);
    }

    public function apply(AffiliateApplicationRequest $request): RedirectResponse
    {
        $customer = auth()->guard('customer')->user();
        $profile = $this->affiliateProfileService->profileForCustomer($customer);

        if ($profile && in_array($profile->status, [AffiliateProfile::STATUS_ACTIVE, AffiliateProfile::STATUS_SUSPENDED], true)) {
            session()->flash('warning', 'Your affiliate status cannot be changed from this form.');

            return redirect()->route('shop.customers.account.affiliate.index');
        }

        $profile = $this->affiliateProfileService->apply($customer, $request->payload());

        session()->flash(
            'success',
            $profile->status === AffiliateProfile::STATUS_PENDING
                ? 'Your affiliate application has been submitted for review.'
                : 'Your affiliate account is active.'
        );

        return redirect()->route('shop.customers.account.affiliate.index');
    }

    public function requestWithdrawal(AffiliateWithdrawalRequest $request): RedirectResponse
    {
        $customer = auth()->guard('customer')->user();
        $profile = $this->affiliateProfileService->profileForCustomer($customer);

        if (! $profile?->isActive()) {
            session()->flash('warning', 'Only active affiliates can request a payout.');

            return redirect()->route('shop.customers.account.affiliate.index');
        }

        $this->affiliatePayoutService->requestPayout(
            $profile,
            (float) $request->validated('amount'),
            $request->payload(),
        );

        session()->flash('success', 'Your payout request has been submitted for admin review.');

        return redirect()->route('shop.customers.account.affiliate.index');
    }
}
