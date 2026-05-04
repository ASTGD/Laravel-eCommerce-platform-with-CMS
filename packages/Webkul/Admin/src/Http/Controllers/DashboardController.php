<?php

namespace Webkul\Admin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Webkul\Admin\Helpers\Dashboard;

class DashboardController extends Controller
{
    /**
     * Request param functions
     *
     * @var array
     */
    protected $typeFunctions = [
        'over-all' => 'getOverAllStats',
        'today' => 'getTodayStats',
        'stock-threshold-products' => 'getStockThresholdProducts',
        'total-sales' => 'getSalesStats',
        'operations-trend' => 'getOperationsTrend',
        'operations-overview' => 'getOperationsOverviewStats',
        'top-selling-products' => 'getTopSellingProducts',
        'top-customers' => 'getTopCustomers',
        'attention' => 'getAttentionStats',
    ];

    /**
     * Create a controller instance.
     *
     * @return void
     */
    public function __construct(protected Dashboard $dashboardHelper) {}

    /**
     * Dashboard page.
     *
     * @return View|JsonResponse
     */
    public function index()
    {
        return view('admin::dashboard.index', [
            'dashboardDateFilter' => $this->dashboardHelper->getDateFilter(),
            'dashboardDateRangeOptions' => $this->dashboardHelper->getDateRangeOptions(),
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function stats()
    {
        $stats = $this->dashboardHelper->{$this->typeFunctions[request()->query('type')]}();

        return response()->json([
            'statistics' => $stats,
            'date_range' => $this->dashboardHelper->getDateRange(),
            'date_filter' => $this->dashboardHelper->getDateFilter(),
        ]);
    }
}
