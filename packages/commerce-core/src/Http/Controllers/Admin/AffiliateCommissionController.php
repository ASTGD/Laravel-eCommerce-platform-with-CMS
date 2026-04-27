<?php

namespace Platform\CommerceCore\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Platform\CommerceCore\Models\AffiliateCommission;
use Platform\CommerceCore\Services\Affiliates\AffiliateCommissionService;

class AffiliateCommissionController extends Controller
{
    public function __construct(protected AffiliateCommissionService $affiliateCommissionService) {}

    public function approve(AffiliateCommission $affiliateCommission): RedirectResponse
    {
        if ($affiliateCommission->status !== AffiliateCommission::STATUS_PENDING) {
            return $this->redirectToProfile($affiliateCommission)
                ->with('warning', 'Only pending affiliate commissions can be approved.');
        }

        $this->affiliateCommissionService->approve($affiliateCommission);

        return $this->redirectToProfile($affiliateCommission)
            ->with('success', 'Affiliate commission approved.');
    }

    public function reverse(Request $request, AffiliateCommission $affiliateCommission): RedirectResponse
    {
        if (! in_array($affiliateCommission->status, [
            AffiliateCommission::STATUS_PENDING,
            AffiliateCommission::STATUS_APPROVED,
        ], true)) {
            return $this->redirectToProfile($affiliateCommission)
                ->with('warning', 'Only pending or approved affiliate commissions can be reversed from this page.');
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $this->affiliateCommissionService->reverse(
            $affiliateCommission,
            $validated['reason'] ?? 'Reversed manually by admin.',
        );

        return $this->redirectToProfile($affiliateCommission)
            ->with('success', 'Affiliate commission reversed.');
    }

    protected function redirectToProfile(AffiliateCommission $affiliateCommission): RedirectResponse
    {
        return redirect()->route('admin.affiliates.profiles.show', [
            'affiliateProfile' => $affiliateCommission->affiliate_profile_id,
            'tab' => 'commissions',
        ]);
    }
}
