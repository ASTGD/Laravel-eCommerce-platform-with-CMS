<?php

namespace Platform\CommerceCore\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Platform\CommerceCore\Services\Affiliates\AffiliateReportService;

class AffiliateReportController extends Controller
{
    public function __construct(protected AffiliateReportService $affiliateReportService) {}

    public function index(Request $request): View
    {
        $rangeDays = $this->rangeDays((int) $request->query('range', 30));
        $report = $this->affiliateReportService->dashboard($rangeDays);

        return view('commerce-core::admin.affiliates.reports.index', [
            ...$report,
            'summary' => $report['summary'],
            'series' => $report['series'],
            'topAffiliates' => $report['top_affiliates'],
            'recentPayouts' => $report['recent_payouts'],
            'rangeDays' => $rangeDays,
            'rangeOptions' => [7, 14, 30, 90],
        ]);
    }

    protected function rangeDays(int $days): int
    {
        return in_array($days, [7, 14, 30, 90], true) ? $days : 30;
    }
}
