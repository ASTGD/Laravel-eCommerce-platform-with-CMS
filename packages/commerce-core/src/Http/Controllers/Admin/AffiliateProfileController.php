<?php

namespace Platform\CommerceCore\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Platform\CommerceCore\Http\Requests\Admin\AffiliatePayoutRecordRequest;
use Platform\CommerceCore\Http\Requests\Admin\AffiliateProfileStoreRequest;
use Platform\CommerceCore\Http\Requests\Admin\AffiliateStatusRequest;
use Platform\CommerceCore\Models\AffiliateClick;
use Platform\CommerceCore\Models\AffiliateCommission;
use Platform\CommerceCore\Models\AffiliateOrderAttribution;
use Platform\CommerceCore\Models\AffiliatePayout;
use Platform\CommerceCore\Models\AffiliateProfile;
use Platform\CommerceCore\Services\Affiliates\AffiliatePayoutService;
use Platform\CommerceCore\Services\Affiliates\AffiliateProfileService;
use Platform\CommerceCore\Services\Affiliates\AffiliateSettingsService;
use Webkul\Customer\Models\Customer;

class AffiliateProfileController extends Controller
{
    public function __construct(
        protected AffiliateProfileService $affiliateProfileService,
        protected AffiliatePayoutService $affiliatePayoutService,
        protected AffiliateSettingsService $affiliateSettingsService,
    ) {}

    public function index(Request $request): View
    {
        $status = $this->resolvedStatus($request->string('status')->value());
        $search = trim($request->string('search')->value());

        $profiles = AffiliateProfile::query()
            ->with('customer')
            ->withCount(['clicks', 'attributions'])
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('referral_code', 'like', "%{$search}%")
                        ->orWhere('application_note', 'like', "%{$search}%")
                        ->orWhereHas('customer', function (Builder $customerQuery) use ($search): void {
                            $customerQuery
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('commerce-core::admin.affiliates.profiles.index', [
            'profiles' => $profiles,
            'status' => $status,
            'search' => $search,
            'statusOptions' => AffiliateProfile::statusLabels(),
            'statusCounts' => $this->statusCounts(),
        ]);
    }

    public function create(): View
    {
        return view('commerce-core::admin.affiliates.profiles.create', [
            'customers' => $this->customersWithoutAffiliateProfile(),
            'statusOptions' => [
                AffiliateProfile::STATUS_ACTIVE => 'Active',
                AffiliateProfile::STATUS_PENDING => 'Pending',
            ],
            'payoutMethods' => $this->affiliateSettingsService->payoutMethods(),
        ]);
    }

    public function store(AffiliateProfileStoreRequest $request): RedirectResponse
    {
        $profile = $this->affiliateProfileService->createFromAdmin(
            Customer::query()->findOrFail($request->integer('customer_id')),
            $request->payload(),
            auth()->guard('admin')->id(),
        );

        return redirect()
            ->route('admin.affiliates.profiles.show', $profile)
            ->with('success', 'Affiliate profile created.');
    }

    public function show(AffiliateProfile $affiliateProfile): View
    {
        $affiliateProfile->load([
            'customer',
            'approvedBy',
            'rejectedBy',
            'suspendedBy',
            'commissions' => fn ($query) => $query->with('order')->latest('id')->limit(20),
            'payouts' => fn ($query) => $query->latest('id')->limit(20),
        ]);

        return view('commerce-core::admin.affiliates.profiles.show', [
            'profile' => $affiliateProfile,
            'trafficSummary' => $this->trafficSummary($affiliateProfile),
            'salesSummary' => $this->salesSummary($affiliateProfile),
            'commissionSummary' => $this->commissionSummary($affiliateProfile),
            'payoutSummary' => $this->payoutSummary($affiliateProfile),
            'balance' => $this->affiliatePayoutService->balanceFor($affiliateProfile),
            'payoutMethods' => $this->affiliateSettingsService->payoutMethods(),
        ]);
    }

    public function approve(AffiliateProfile $affiliateProfile): RedirectResponse
    {
        $this->affiliateProfileService->approve($affiliateProfile, auth()->guard('admin')->id());

        return redirect()
            ->route('admin.affiliates.profiles.show', $affiliateProfile)
            ->with('success', 'Affiliate approved.');
    }

    public function reject(AffiliateStatusRequest $request, AffiliateProfile $affiliateProfile): RedirectResponse
    {
        $this->affiliateProfileService->reject($affiliateProfile, auth()->guard('admin')->id(), $request->reason());

        return redirect()
            ->route('admin.affiliates.profiles.show', $affiliateProfile)
            ->with('success', 'Affiliate rejected.');
    }

    public function suspend(AffiliateStatusRequest $request, AffiliateProfile $affiliateProfile): RedirectResponse
    {
        $this->affiliateProfileService->suspend($affiliateProfile, auth()->guard('admin')->id(), $request->reason());

        return redirect()
            ->route('admin.affiliates.profiles.show', $affiliateProfile)
            ->with('success', 'Affiliate suspended.');
    }

    public function reactivate(AffiliateProfile $affiliateProfile): RedirectResponse
    {
        $this->affiliateProfileService->reactivate($affiliateProfile, auth()->guard('admin')->id());

        return redirect()
            ->route('admin.affiliates.profiles.show', $affiliateProfile)
            ->with('success', 'Affiliate reactivated.');
    }

    public function regenerateReferralCode(AffiliateProfile $affiliateProfile): RedirectResponse
    {
        $this->affiliateProfileService->regenerateReferralCode($affiliateProfile, auth()->guard('admin')->id());

        return redirect()
            ->route('admin.affiliates.profiles.show', $affiliateProfile)
            ->with('success', 'Referral code regenerated. The previous referral link no longer creates new attribution.');
    }

    public function storePayout(AffiliatePayoutRecordRequest $request, AffiliateProfile $affiliateProfile): RedirectResponse
    {
        $this->affiliatePayoutService->recordPaidPayout(
            $affiliateProfile,
            $request->amount(),
            $request->payload(),
            auth()->guard('admin')->id(),
        );

        return redirect()
            ->route('admin.affiliates.profiles.show', $affiliateProfile)
            ->with('success', 'Payout record added.');
    }

    protected function resolvedStatus(?string $status): ?string
    {
        return in_array($status, AffiliateProfile::statuses(), true)
            ? $status
            : AffiliateProfile::STATUS_PENDING;
    }

    protected function statusCounts(): array
    {
        $counts = AffiliateProfile::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->all();

        return collect(AffiliateProfile::statuses())
            ->mapWithKeys(fn (string $status) => [$status => (int) ($counts[$status] ?? 0)])
            ->all();
    }

    protected function trafficSummary(AffiliateProfile $profile): array
    {
        return [
            'clicks' => AffiliateClick::query()->where('affiliate_profile_id', $profile->id)->count(),
            'recent_clicks' => AffiliateClick::query()
                ->where('affiliate_profile_id', $profile->id)
                ->where('clicked_at', '>=', now()->subDays(30))
                ->count(),
        ];
    }

    protected function salesSummary(AffiliateProfile $profile): array
    {
        return [
            'attributed_orders' => AffiliateOrderAttribution::query()
                ->where('affiliate_profile_id', $profile->id)
                ->where('status', AffiliateOrderAttribution::STATUS_ATTRIBUTED)
                ->count(),
        ];
    }

    protected function commissionSummary(AffiliateProfile $profile): array
    {
        return [
            'pending' => $this->sumCommissions($profile, AffiliateCommission::STATUS_PENDING),
            'approved' => $this->sumCommissions($profile, AffiliateCommission::STATUS_APPROVED),
            'paid' => $this->sumCommissions($profile, AffiliateCommission::STATUS_PAID),
            'reversed' => $this->sumCommissions($profile, AffiliateCommission::STATUS_REVERSED),
        ];
    }

    protected function payoutSummary(AffiliateProfile $profile): array
    {
        return [
            'requested' => $this->sumPayouts($profile, AffiliatePayout::STATUS_REQUESTED),
            'approved' => $this->sumPayouts($profile, AffiliatePayout::STATUS_APPROVED),
            'paid' => $this->sumPayouts($profile, AffiliatePayout::STATUS_PAID),
            'rejected' => $this->sumPayouts($profile, AffiliatePayout::STATUS_REJECTED),
        ];
    }

    protected function sumCommissions(AffiliateProfile $profile, string $status): float
    {
        return (float) AffiliateCommission::query()
            ->where('affiliate_profile_id', $profile->id)
            ->where('status', $status)
            ->sum('commission_amount');
    }

    protected function sumPayouts(AffiliateProfile $profile, string $status): float
    {
        return (float) AffiliatePayout::query()
            ->where('affiliate_profile_id', $profile->id)
            ->where('status', $status)
            ->sum('amount');
    }

    protected function customersWithoutAffiliateProfile()
    {
        return Customer::query()
            ->whereNotIn('id', AffiliateProfile::query()->select('customer_id'))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(200)
            ->get(['id', 'first_name', 'last_name', 'email', 'phone']);
    }
}
