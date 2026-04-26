<?php

namespace Platform\CommerceCore\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Platform\CommerceCore\Http\Requests\Admin\AffiliateSettingsRequest;
use Platform\CommerceCore\Services\Affiliates\AffiliateSettingsService;

class AffiliateSettingsController extends Controller
{
    public function __construct(protected AffiliateSettingsService $affiliateSettingsService) {}

    public function index(): View
    {
        $settings = $this->affiliateSettingsService->all();

        return view('commerce-core::admin.affiliates.settings.index', [
            'settings' => $settings,
            'payoutMethodsText' => $this->affiliateSettingsService->payoutMethodsText($settings['payout_methods'] ?? []),
        ]);
    }

    public function update(AffiliateSettingsRequest $request): RedirectResponse
    {
        $this->affiliateSettingsService->update($request->payload());

        return redirect()
            ->route('admin.affiliates.settings.index')
            ->with('success', 'Affiliate settings saved.');
    }
}
