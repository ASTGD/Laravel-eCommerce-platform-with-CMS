<?php

namespace Webkul\Shop\Http\Controllers\Customer\Account;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Platform\CommerceCore\Services\Reviews\OrderItemReviewEligibilityService;
use Webkul\Checkout\Facades\Cart;
use Webkul\Core\Traits\PDFHandler;
use Webkul\Product\Repositories\ProductReviewAttachmentRepository;
use Webkul\Product\Repositories\ProductReviewRepository;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Shop\DataGrids\OrderDataGrid;
use Webkul\Shop\Http\Controllers\Controller;

class OrderController extends Controller
{
    use PDFHandler;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected OrderRepository $orderRepository,
        protected InvoiceRepository $invoiceRepository,
        protected ProductReviewRepository $productReviewRepository,
        protected ProductReviewAttachmentRepository $productReviewAttachmentRepository,
        protected OrderItemReviewEligibilityService $reviewEligibilityService,
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(OrderDataGrid::class)->process();
        }

        return view('shop::customers.account.orders.index');
    }

    /**
     * Show the view for the specified resource.
     *
     * @param  int  $id
     * @return View
     */
    public function view($id)
    {
        $order = $this->orderRepository->findOneWhere([
            'customer_id' => auth()->guard('customer')->id(),
            'id' => $id,
        ]);

        abort_if(! $order, 404);

        $customer = auth()->guard('customer')->user();

        $order->loadMissing(['items.product', 'items.order']);

        $reviewStates = $order->items
            ->mapWithKeys(fn ($item) => [
                $item->id => $this->reviewEligibilityService->stateForOrderItem($item, $customer),
            ])
            ->all();

        $showReviewColumn = $this->reviewEligibilityService->reviewsEnabled();

        return view('shop::customers.account.orders.view', compact('order', 'reviewStates', 'showReviewColumn'));
    }

    public function createReview(int $orderId, int $itemId): View|RedirectResponse
    {
        $order = $this->findCustomerOrder($orderId);
        $item = $order->items()->with('product', 'order')->findOrFail($itemId);
        $customer = auth()->guard('customer')->user();

        if (! $this->reviewEligibilityService->canReviewOrderItem($item, $customer)) {
            session()->flash('warning', trans('shop::app.customers.account.orders.view.review.not-eligible'));

            return redirect()->route('shop.customers.account.orders.view', $order->id);
        }

        return view('shop::customers.account.orders.review', compact('order', 'item'));
    }

    public function storeReview(int $orderId, int $itemId): RedirectResponse
    {
        $order = $this->findCustomerOrder($orderId);
        $item = $order->items()->with('product', 'order')->findOrFail($itemId);
        $customer = auth()->guard('customer')->user();

        if (! $this->reviewEligibilityService->canReviewOrderItem($item, $customer)) {
            throw ValidationException::withMessages([
                'review' => trans('shop::app.customers.account.orders.view.review.not-eligible'),
            ]);
        }

        $validated = request()->validate([
            'title' => 'required|string|max:255',
            'comment' => 'required|string',
            'rating' => 'required|numeric|min:1|max:5',
            'attachments' => 'array',
            'attachments.*' => 'file|max:10240|extensions:jpg,jpeg,png,webp,mp4,webm,mov|mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime',
        ]);

        $productId = $this->reviewEligibilityService->reviewableProductId($item);

        Event::dispatch('customer.review.create.before', $productId);

        $review = $this->productReviewRepository->create([
            'title' => $validated['title'],
            'comment' => $validated['comment'],
            'rating' => $validated['rating'],
            'status' => 'pending',
            'product_id' => $productId,
            'customer_id' => $customer->id,
            'name' => $customer->name,
        ]);

        $this->productReviewAttachmentRepository->upload(request()->file('attachments') ?? [], $review);

        Event::dispatch('customer.review.create.after', $review);

        session()->flash('success', trans('shop::app.customers.account.orders.view.review.success'));

        return redirect()->route('shop.customers.account.orders.view', $order->id);
    }

    /**
     * Reorder action for the specified resource.
     *
     * @return Response
     */
    public function reorder(int $id)
    {
        $order = $this->orderRepository->findOneWhere([
            'customer_id' => auth()->guard('customer')->id(),
            'id' => $id,
        ]);

        abort_if(! $order, 404);

        foreach ($order->items as $item) {
            try {
                Cart::addProduct($item->product, $item->additional);
            } catch (\Exception $e) {
            }
        }

        return redirect()->route('shop.checkout.cart.index');
    }

    /**
     * Print and download the for the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function printInvoice($id)
    {
        $invoice = $this->invoiceRepository->where('id', $id)
            ->whereHas('order', function ($query) {
                $query->where('customer_id', auth()->guard('customer')->id());
            })
            ->firstOrFail();

        $orderCurrencyCode = $invoice->order->order_currency_code;

        return $this->downloadPDF(
            view('shop::customers.account.orders.pdf', compact('invoice', 'orderCurrencyCode'))->render(),
            'invoice-'.$invoice->created_at->format('d-m-Y')
        );
    }

    /**
     * Cancel action for the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function cancel($id)
    {
        $customer = auth()->guard('customer')->user();

        /* find by order id in customer's order */
        $order = $customer->orders()->find($id);

        /* if order id not found then process should be aborted with 404 page */
        if (! $order) {
            abort(404);
        }

        $result = $this->orderRepository->cancel($order);

        if ($result) {
            session()->flash('success', trans('shop::app.customers.account.orders.view.cancel-success', ['name' => trans('shop::app.customers.account.orders.order')]));
        } else {
            session()->flash('error', trans('shop::app.customers.account.orders.view.cancel-error', ['name' => trans('shop::app.customers.account.orders.order')]));
        }

        return redirect()->back();
    }

    protected function findCustomerOrder(int $orderId)
    {
        $order = $this->orderRepository->findOneWhere([
            'customer_id' => auth()->guard('customer')->id(),
            'id' => $orderId,
        ]);

        abort_if(! $order, 404);

        return $order;
    }
}
