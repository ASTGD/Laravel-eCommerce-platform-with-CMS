<?php

namespace Platform\CommerceCore\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Platform\CommerceCore\Services\Affiliates\AffiliateReportService;

class AffiliateReportController extends Controller
{
    public function __construct(protected AffiliateReportService $affiliateReportService) {}

    public function index(): View
    {
        return view('commerce-core::admin.affiliates.reports.index', [
            'summary' => $this->affiliateReportService->summary(),
            'series' => $this->affiliateReportService->dailySeries(),
            'topAffiliates' => $this->affiliateReportService->topAffiliates(),
        ]);
    }
}
