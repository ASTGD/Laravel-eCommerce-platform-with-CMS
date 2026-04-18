<?php

namespace Platform\CommerceCore\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Platform\CommerceCore\Repositories\OrderRepository;
use Webkul\Sales\Models\Order;

class OrderStatusController extends Controller
{
    public function __construct(
        protected OrderRepository $orderRepository,
    ) {}

    public function confirm(Order $order): RedirectResponse
    {
        if (! $order->canConfirm()) {
            return redirect()
                ->route('admin.sales.orders.view', $order->id)
                ->with('error', trans('admin::app.sales.orders.view.confirm-error'));
        }

        $this->orderRepository->updateOrderStatus($order, Order::STATUS_PROCESSING);

        return redirect()
            ->route('admin.sales.orders.view', $order->id)
            ->with('success', trans('admin::app.sales.orders.view.confirm-success'));
    }
}
