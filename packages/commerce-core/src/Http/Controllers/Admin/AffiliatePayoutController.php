<?php

namespace Platform\CommerceCore\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Platform\CommerceCore\Http\Requests\Admin\AffiliatePayoutStatusRequest;
use Platform\CommerceCore\Models\AffiliatePayout;
use Platform\CommerceCore\Services\Affiliates\AffiliatePayoutService;

class AffiliatePayoutController extends Controller
{
    public function __construct(protected AffiliatePayoutService $affiliatePayoutService) {}

    public function index(Request $request): View
    {
        $status = $this->resolvedStatus($request->string('status')->value());
        $search = trim($request->string('search')->value());

        $payouts = AffiliatePayout::query()
            ->with(['affiliateProfile.customer'])
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('payout_reference', 'like', "%{$search}%")
                        ->orWhere('payout_method', 'like', "%{$search}%")
                        ->orWhereHas('affiliateProfile', function (Builder $profileQuery) use ($search): void {
                            $profileQuery
                                ->where('referral_code', 'like', "%{$search}%")
                                ->orWhereHas('customer', function (Builder $customerQuery) use ($search): void {
                                    $customerQuery
                                        ->where('first_name', 'like', "%{$search}%")
                                        ->orWhere('last_name', 'like', "%{$search}%")
                                        ->orWhere('email', 'like', "%{$search}%");
                                });
                        });
                });
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('commerce-core::admin.affiliates.payouts.index', [
            'payouts' => $payouts,
            'status' => $status,
            'search' => $search,
            'statusOptions' => AffiliatePayout::statusLabels(),
            'statusCounts' => $this->statusCounts(),
        ]);
    }

    public function approve(AffiliatePayoutStatusRequest $request, AffiliatePayout $affiliatePayout): RedirectResponse
    {
        $this->affiliatePayoutService->approve(
            $affiliatePayout,
            auth()->guard('admin')->id(),
            $request->nullableString('admin_notes'),
        );

        return redirect()
            ->route('admin.affiliates.payouts.index', ['status' => AffiliatePayout::STATUS_APPROVED])
            ->with('success', 'Payout approved.');
    }

    public function markPaid(AffiliatePayoutStatusRequest $request, AffiliatePayout $affiliatePayout): RedirectResponse
    {
        $this->affiliatePayoutService->markPaid(
            $affiliatePayout,
            auth()->guard('admin')->id(),
            $request->nullableString('payout_reference'),
        );

        return redirect()
            ->route('admin.affiliates.payouts.index', ['status' => AffiliatePayout::STATUS_PAID])
            ->with('success', 'Payout marked paid.');
    }

    public function reject(AffiliatePayoutStatusRequest $request, AffiliatePayout $affiliatePayout): RedirectResponse
    {
        $this->affiliatePayoutService->reject(
            $affiliatePayout,
            auth()->guard('admin')->id(),
            $request->nullableString('reason'),
        );

        return redirect()
            ->route('admin.affiliates.payouts.index', ['status' => AffiliatePayout::STATUS_REJECTED])
            ->with('success', 'Payout rejected.');
    }

    protected function resolvedStatus(?string $status): ?string
    {
        return in_array($status, array_keys(AffiliatePayout::statusLabels()), true)
            ? $status
            : AffiliatePayout::STATUS_REQUESTED;
    }

    protected function statusCounts(): array
    {
        $counts = AffiliatePayout::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->all();

        return collect(array_keys(AffiliatePayout::statusLabels()))
            ->mapWithKeys(fn (string $status) => [$status => (int) ($counts[$status] ?? 0)])
            ->all();
    }
}
