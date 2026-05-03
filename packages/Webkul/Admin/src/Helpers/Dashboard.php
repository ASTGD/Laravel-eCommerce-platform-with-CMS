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
     * Create a controller instance.
     *
     * @return void
     */
    public function __construct(
        protected Sale $saleReporting,
        protected Product $productReporting,
        protected Customer $customerReporting,
        protected AdminOperationsDashboardService $operationsDashboardService
    ) {}

    /**
     * Returns the overall statistics.
     */
    public function getOverAllStats(): array
    {
        $pendingInvoices = $this->getPendingInvoiceSummary();

        return [
            'total_customers' => $this->customerReporting->getTotalCustomersProgress(),
            'total_orders' => $this->saleReporting->getTotalOrdersProgress(),
            'total_sales' => $this->saleReporting->getTotalSalesProgress(),
            'avg_sales' => $this->saleReporting->getAverageSalesProgress(),
            'total_unpaid_invoices' => [
                'total' => $pendingInvoices['total'],
                'formatted_total' => $pendingInvoices['formatted_total'],
                'count' => $pendingInvoices['count'],
            ],
            ...$this->operationsDashboardService->executiveMetrics(),
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
        return $this->operationsDashboardService->operationsOverview();
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
     * Returns unpaid invoice totals and recent records.
     */
    protected function getPendingInvoiceSummary(int $limit = 0): array
    {
        $query = Invoice::query()->with('order')->where('state', Invoice::STATUS_PENDING);
        $total = (float) $this->saleReporting->getTotalPendingInvoicesAmount();
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
}
