<?php

namespace Platform\CommerceCore\Http\Controllers\API;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Platform\CommerceCore\Services\PickupPointService;
use Platform\CommerceCore\Transformers\OrderResource;
use Webkul\Checkout\Facades\Cart;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\Payment\Facades\Payment;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Shop\Http\Controllers\API\OnepageController as BaseOnepageController;
use Webkul\Shop\Http\Resources\CartResource;

class OnepageController extends BaseOnepageController
{
    public function __construct(
        OrderRepository $orderRepository,
        CustomerRepository $customerRepository,
        protected PickupPointService $pickupPointService,
    ) {
        parent::__construct($orderRepository, $customerRepository);
    }

    public function storeShippingMethod()
    {
        $validatedData = $this->validate(request(), [
            'shipping_method' => 'required',
            'pickup_point_id' => [
                Rule::requiredIf(fn () => $this->pickupPointService->isPickupMethod(request('shipping_method'))),
                'nullable',
                Rule::exists('pickup_points', 'id')->where(fn ($query) => $query->where('is_active', 1)),
            ],
        ]);

        if (
            Cart::hasError()
            || ! $validatedData['shipping_method']
            || ! Cart::saveShippingMethod($validatedData['shipping_method'])
        ) {
            return response()->json([
                'redirect_url' => route('shop.checkout.cart.index'),
            ], Response::HTTP_FORBIDDEN);
        }

        $shippingAddress = Cart::getCart()?->shipping_address;

        if ($this->pickupPointService->isPickupMethod($validatedData['shipping_method'])) {
            $pickupPoint = $this->pickupPointService->requireActive($validatedData['pickup_point_id'] ?? null);

            $this->pickupPointService->assignToAddress($shippingAddress, $pickupPoint);
        } else {
            $this->pickupPointService->clearFromAddress($shippingAddress);
        }

        Cart::refreshCart();
        Cart::collectTotals();

        return response()->json(Payment::getSupportedPaymentMethods());
    }

    public function storeOrder()
    {
        if (Cart::hasError()) {
            return new JsonResource([
                'redirect' => true,
                'redirect_url' => route('shop.checkout.cart.index'),
            ]);
        }

        Cart::collectTotals();

        try {
            $this->validateOrder();
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }

        $cart = Cart::getCart();

        if ($redirectUrl = Payment::getRedirectUrl($cart)) {
            return new JsonResource([
                'redirect' => true,
                'redirect_url' => $redirectUrl,
            ]);
        }

        $data = (new OrderResource($cart))->jsonSerialize();

        $order = $this->orderRepository->create($data);

        Cart::deActivateCart();

        session()->flash('order_id', $order->id);

        return new JsonResource([
            'redirect' => true,
            'redirect_url' => route('shop.checkout.onepage.success'),
        ]);
    }

    public function validateOrder()
    {
        parent::validateOrder();

        $cart = Cart::getCart();

        if (
            $cart->haveStockableItems()
            && $this->pickupPointService->isPickupMethod($cart->shipping_method)
        ) {
            $this->pickupPointService->assertValidSelection($cart->shipping_address);
        }
    }
}
