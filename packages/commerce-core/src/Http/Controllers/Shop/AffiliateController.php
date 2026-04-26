<?php

namespace Platform\CommerceCore\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use Platform\CommerceCore\Http\Requests\Shop\AffiliateApplicationRequest;
use Platform\CommerceCore\Http\Requests\Shop\AffiliateWithdrawalRequest;
use Platform\CommerceCore\Models\AffiliateProfile;
use Platform\CommerceCore\Services\Affiliates\AffiliatePayoutService;
use Platform\CommerceCore\Services\Affiliates\AffiliatePortalService;
use Platform\CommerceCore\Services\Affiliates\AffiliateProfileService;
use Platform\CommerceCore\Services\Affiliates\AffiliateReferralLinkService;
use Platform\CommerceCore\Services\Affiliates\AffiliateSettingsService;

class AffiliateController extends Controller
{
    public function __construct(
        protected AffiliateProfileService $affiliateProfileService,
        protected AffiliatePortalService $affiliatePortalService,
        protected AffiliatePayoutService $affiliatePayoutService,
        protected AffiliateSettingsService $affiliateSettingsService,
        protected AffiliateReferralLinkService $affiliateReferralLinkService,
    ) {}

    public function program(): View
    {
        $customer = auth()->guard('customer')->user();

        return view('commerce-core::shop.affiliate-program', [
            'customer' => $customer,
            'profile' => $this->affiliateProfileService->profileForCustomer($customer),
            'portalState' => $this->affiliateProfileService->portalState($customer),
            'settings' => [
                'approval_required' => $this->affiliateSettingsService->approvalRequired(),
                'cookie_window_days' => $this->affiliateSettingsService->cookieWindowDays(),
                'default_commission' => $this->affiliateSettingsService->defaultCommission(),
            ],
        ]);
    }

    public function index(Request $request): View
    {
        $customer = auth()->guard('customer')->user();
        $profile = $this->affiliateProfileService->profileForCustomer($customer);
        $dashboard = $profile?->isActive() ? $this->affiliatePortalService->dashboardFor($profile) : null;
        $referralBuilder = $profile?->isActive() ? $this->referralBuilderPayload($profile, $request) : null;

        return view('commerce-core::shop.customers.account.affiliate.index', [
            'customer' => $customer,
            'profile' => $profile,
            'portalState' => $this->affiliateProfileService->portalState($customer),
            'dashboard' => $dashboard,
            'referralBuilder' => $referralBuilder,
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

    protected function referralBuilderPayload(AffiliateProfile $profile, Request $request): array
    {
        $targetPath = $request->query('target_path');

        try {
            $normalizedTarget = $this->affiliateReferralLinkService->normalizeInternalTarget(is_string($targetPath) ? $targetPath : null);

            return [
                'target_path' => $normalizedTarget,
                'generated_url' => $this->affiliateReferralLinkService->build($profile, $normalizedTarget),
                'error' => null,
            ];
        } catch (InvalidArgumentException $exception) {
            return [
                'target_path' => '/',
                'generated_url' => $this->affiliateReferralLinkService->build($profile),
                'error' => $exception->getMessage(),
            ];
        }
    }
}
