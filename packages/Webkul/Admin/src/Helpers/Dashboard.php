<?php

namespace Webkul\Admin\Helpers;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Platform\CommerceCore\Services\AdminOperationsDashboardService;
use Webkul\Admin\Helpers\Reporting\Customer;
use Webkul\Admin\Helpers\Reporting\Product;
use Webkul\Admin\Helpers\Reporting\Sale;
use Webkul\Sales\Models\Invoice;

class Dashboard
{
    /**
     * Dashboard date filter options.
     */
    protected const DATE_RANGES = [
        'today' => 'Today',
        'last_7_days' => 'Last 7 Days',
        'last_30_days' => 'Last 30 Days',
        'this_month' => 'This Month',
    ];

    /**
     * Resolved dashboard date filter state.
     */
    protected array $dateFilter;

    /**
     * Create a controller instance.
     *
     * @return void
     */
    public function __construct(
        protected Sale $saleReporting,
        protected Product $productReporting,
        protected Customer $customerReporting,
        protected AdminOperationsDashboardService $operationsDashboardService
    ) {
        $this->dateFilter = $this->resolveDateFilter();

        if ($this->dateFilter['is_filtered']) {
            $this->applyDateRange($this->dateFilter['start_date'], $this->dateFilter['end_date']);
        }
    }

    /**
     * Returns the supported dashboard date range options.
     */
    public function getDateRangeOptions(): array
    {
        return self::DATE_RANGES;
    }

    /**
     * Returns the resolved dashboard date filter state.
     */
    public function getDateFilter(): array
    {
        return $this->dateFilter;
    }

    /**
     * Returns the overall statistics.
     */
    public function getOverAllStats(): array
    {
        $pendingInvoices = $this->getPendingInvoiceSummary();

        return [
            'comparison_label' => $this->getComparisonLabel(),
            'total_customers' => $this->customerReporting->getTotalCustomersProgress(),
            'total_orders' => $this->saleReporting->getTotalOrdersProgress(),
            'total_sales' => $this->saleReporting->getTotalSalesProgress(),
            'avg_sales' => $this->saleReporting->getAverageSalesProgress(),
            'total_unpaid_invoices' => [
                'total' => $pendingInvoices['total'],
                'formatted_total' => $pendingInvoices['formatted_total'],
                'count' => $pendingInvoices['count'],
            ],
            ...$this->operationsDashboardService->executiveMetrics($this->getStartDate(), $this->getEndDate()),
        ];
    }

    /**
     * Returns the today statistics.
     */
    public function getTodayStats(): array
    {
        $orders = $this->saleReporting->getTodayOrders();

        $orders = $orders->map(function ($order) {
            return [
                'id' => $order->id,
                'increment_id' => $order->increment_id ?: $order->id,
                'status' => $order->status,
                'status_label' => $order->status_label,
                'payment_method' => $order->payment
                    ? core()->getConfigData('sales.payment_methods.'.$order->payment->method.'.title')
                    : 'N/A',
                'base_grand_total' => $order->base_grand_total,
                'formatted_base_grand_total' => core()->formatBasePrice($order->base_grand_total),
                'channel_name' => $order->channel_name,
                'customer_email' => $order->customer_email,
                'customer_name' => $order->customer_full_name,
                'items' => view('admin::sales.orders.items', compact('order'))->render(),
                'billing_address' => $order?->billing_address->city.($order?->billing_address->country ? ', '.core()->country_name($order?->billing_address->country) : ''),
                'created_at' => $order->created_at->format('d M Y, H:i:s'),
            ];
        });

        return [
            'total_sales' => $this->saleReporting->getTodaySalesProgress(),
            'total_orders' => $this->saleReporting->getTodayOrdersProgress(),
            'total_customers' => $this->customerReporting->getTodayCustomersProgress(),
            'orders' => $orders,
        ];
    }

    /**
     * Returns the today statistics.
     *
     * @return EloquentCollection
     */
    public function getStockThresholdProducts()
    {
        $products = $this->productReporting->getStockThresholdProducts(5);

        $products = $products->map(function ($product) {
            return [
                'id' => $product->product_id,
                'sku' => $product->product->sku,
                'name' => $product->product->name,
                'price' => $product->product->price,
                'formatted_price' => core()->formatBasePrice($product->product->price),
                'total_qty' => $product->total_qty,
                'image' => $product->product->base_image_url,
            ];
        });

        return $products;
    }

    /**
     * Returns sales statistics.
     */
    public function getSalesStats(): array
    {
        return [
            'total_orders' => $this->saleReporting->getTotalOrdersProgress(),
            'total_sales' => $this->saleReporting->getTotalSalesProgress(),
            'over_time' => $this->saleReporting->getCurrentTotalSalesOverTime(),
        ];
    }

    /**
     * Returns operations trend statistics.
     */
    public function getOperationsTrend(): array
    {
        return $this->operationsDashboardService->shipmentTrend(
            $this->getStartDate(),
            $this->getEndDate(),
            $this->saleReporting->getTimeInterval($this->getStartDate(), $this->getEndDate(), 'auto')
        );
    }

    /**
     * Returns daily operations overview statistics.
     */
    public function getOperationsOverviewStats(): array
    {
        return $this->operationsDashboardService->operationsOverview($this->getStartDate(), $this->getEndDate());
    }

    /**
     * Returns attention-needed statistics.
     */
    public function getAttentionStats(): array
    {
        return [
            'unpaid_invoices' => $this->getPendingInvoiceSummary(5),
            'shipment_attention' => $this->operationsDashboardService->attentionSummary(),
        ];
    }

    /**
     * Returns top selling products statistics.
     */
    public function getTopSellingProducts(): Collection
    {
        return $this->productReporting->getTopSellingProductsByRevenue(5);
    }

    /**
     * Returns top customers statistics.
     */
    public function getTopCustomers(): EloquentCollection
    {
        $customers = $this->customerReporting->getCustomersWithMostSales(5);

        $customers->map(function ($customer) {
            $customer->formatted_total = core()->formatBasePrice($customer->total);
        });

        return $customers;
    }

    /**
     * Get the start date.
     *
     * @return \Carbon\Carbon
     */
    public function getStartDate(): Carbon
    {
        return $this->saleReporting->getStartDate();
    }

    /**
     * Get the end date.
     *
     * @return \Carbon\Carbon
     */
    public function getEndDate(): Carbon
    {
        return $this->saleReporting->getEndDate();
    }

    /**
     * Returns date range
     */
    public function getDateRange(): string
    {
        return $this->getStartDate()->format('d M').' - '.$this->getEndDate()->format('d M');
    }

    /**
     * Returns the comparison range label for KPI cards.
     */
    public function getComparisonLabel(): string
    {
        $range = $this->dateFilter['selected'] ?? $this->dateFilter['requested'] ?? null;

        return match ($range) {
            'today' => 'vs yesterday',
            'last_7_days' => 'vs last 7 days',
            'last_30_days', null => 'vs last 30 days',
            'this_month' => 'vs last month',
            'custom' => $this->customComparisonLabel(),
            default => 'vs previous period',
        };
    }

    /**
     * Returns a comparison label for custom date ranges.
     */
    protected function customComparisonLabel(): string
    {
        $days = max(1, (int) $this->getStartDate()->diffInDays($this->getEndDate()) + 1);

        return $days === 1
            ? 'vs yesterday'
            : 'vs last '.$days.' days';
    }

    /**
     * Returns unpaid invoice totals and recent records.
     */
    protected function getPendingInvoiceSummary(int $limit = 0): array
    {
        $query = Invoice::query()
            ->with('order')
            ->where('state', Invoice::STATUS_PENDING)
            ->whereBetween('created_at', [$this->getStartDate(), $this->getEndDate()]);

        $total = (float) (clone $query)->sum('grand_total');
        $recent = collect();

        if ($limit > 0) {
            $recent = (clone $query)
                ->latest('id')
                ->limit($limit)
                ->get()
                ->map(fn (Invoice $invoice) => [
                    'id' => $invoice->id,
                    'order_id' => $invoice->order_id,
                    'order_increment_id' => $invoice->order?->increment_id ?: $invoice->order_id,
                    'state' => $invoice->state,
                    'state_label' => $invoice->status_label,
                    'grand_total' => (float) $invoice->grand_total,
                    'formatted_grand_total' => core()->formatBasePrice((float) $invoice->grand_total),
                    'created_at' => $invoice->created_at?->format('d M Y'),
                ]);
        }

        return [
            'total' => $total,
            'formatted_total' => core()->formatBasePrice($total),
            'count' => (clone $query)->count(),
            'recent' => $recent,
        ];
    }

    /**
     * Applies the selected dashboard range to date-aware reporting helpers.
     */
    protected function applyDateRange(Carbon $startDate, Carbon $endDate): void
    {
        $this->saleReporting->setDateRange($startDate, $endDate);
        $this->productReporting->setDateRange($startDate, $endDate);
        $this->customerReporting->setDateRange($startDate, $endDate);
    }

    /**
     * Resolves dashboard filter query parameters into a safe date range.
     */
    protected function resolveDateFilter(): array
    {
        $range = request()->query('range');

        if (request()->filled('from') || request()->filled('to') || request()->filled('start') || request()->filled('end')) {
            return $this->customDateFilter(
                request()->query('from', request()->query('start')),
                request()->query('to', request()->query('end'))
            );
        }

        if (! array_key_exists($range, self::DATE_RANGES)) {
            return $this->defaultDateFilter($range);
        }

        $now = now();

        [$startDate, $endDate] = match ($range) {
            'today' => [$now->copy()->startOfDay(), $now],
            'last_7_days' => [$now->copy()->subDays(6)->startOfDay(), $now],
            'last_30_days' => [$now->copy()->subDays(29)->startOfDay(), $now],
            'this_month' => [$now->copy()->startOfMonth(), $now],
        };

        return $this->dateFilterState($range, $startDate, $endDate, [
            'range' => $range,
        ]);
    }

    /**
     * Builds a custom date filter or falls back to the default range.
     */
    protected function customDateFilter(mixed $from, mixed $to): array
    {
        $startDate = $this->parseDateInput($from);
        $endDate = $this->parseDateInput($to);

        if (! $startDate || ! $endDate || $startDate->gt($endDate) || $startDate->isFuture() || $endDate->startOfDay()->isFuture()) {
            return $this->defaultDateFilter('custom');
        }

        $endDate = $endDate->isToday()
            ? now()
            : $endDate->endOfDay();

        return $this->dateFilterState('custom', $startDate->startOfDay(), $endDate, [
            'range' => 'custom',
            'from' => $startDate->toDateString(),
            'to' => $endDate->toDateString(),
        ]);
    }

    /**
     * Returns default filter state without changing the native dashboard range.
     */
    protected function defaultDateFilter(?string $requestedRange = null): array
    {
        return [
            'selected' => null,
            'requested' => $requestedRange,
            'is_filtered' => false,
            'start_date' => $this->getStartDate(),
            'end_date' => $this->getEndDate(),
            'from' => $this->getStartDate()->toDateString(),
            'to' => $this->getEndDate()->toDateString(),
            'query' => [],
        ];
    }

    /**
     * Formats a resolved dashboard filter state.
     */
    protected function dateFilterState(string $selected, Carbon $startDate, Carbon $endDate, array $query): array
    {
        return [
            'selected' => $selected,
            'requested' => $selected,
            'is_filtered' => true,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'from' => $startDate->toDateString(),
            'to' => $endDate->toDateString(),
            'query' => $query,
        ];
    }

    /**
     * Parses a YYYY-MM-DD date input without accepting partial or malformed dates.
     */
    protected function parseDateInput(mixed $value): ?Carbon
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }

        return $date?->format('Y-m-d') === $value ? $date : null;
    }
}
