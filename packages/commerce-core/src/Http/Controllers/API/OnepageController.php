<?php

namespace Platform\CommerceCore\Http\Controllers\API;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Platform\CommerceCore\Services\DistrictShippingService;
use Platform\CommerceCore\Services\PickupPointService;
use Platform\CommerceCore\Transformers\CheckoutStateResource;
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
        protected DistrictShippingService $districtShippingService,
        protected PickupPointService $pickupPointService,
    ) {
        parent::__construct($orderRepository, $customerRepository);
    }

    public function state(): JsonResource
    {
        $this->ensureDefaultPaymentMethod();

        return new CheckoutStateResource(Cart::getCart());
    }

    public function storeAddress(\Webkul\Shop\Http\Requests\CartAddressRequest $cartAddressRequest): JsonResource
    {
        $params = $cartAddressRequest->all();

        if (
            ! auth()->guard('customer')->check()
            && Cart::getCart()->hasDownloadableItems()
        ) {
            return new JsonResource([
                'redirect' => true,
                'data' => route('shop.customer.session.index'),
            ]);
        }

        if (Cart::hasError()) {
            return new JsonResource([
                'redirect' => true,
                'redirect_url' => route('shop.checkout.cart.index'),
            ]);
        }

        Cart::saveAddresses($params);

        $cart = Cart::getCart();

        if ($cart->haveStockableItems()) {
            if (! $shippingMethods = $this->districtShippingService->applyAutomaticRate()) {
                return new JsonResource([
                    'redirect' => true,
                    'redirect_url' => route('shop.checkout.cart.index'),
                ]);
            }

            return new JsonResource([
                'redirect' => false,
                'data' => array_merge(
                    [
                        'shippingMethods' => $shippingMethods,
                    ],
                    [
                        'payment_methods' => CheckoutStateResource::preferredPaymentMethods(
                            Payment::getSupportedPaymentMethods()['payment_methods'] ?? []
                        ),
                    ],
                ),
            ]);
        }

        Cart::collectTotals();

        return new JsonResource([
            'redirect' => false,
            'data' => [
                'payment_methods' => CheckoutStateResource::preferredPaymentMethods(
                    Payment::getSupportedPaymentMethods()['payment_methods'] ?? []
                ),
            ],
        ]);
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
        ) {
            return response()->json([
                'redirect_url' => route('shop.checkout.cart.index'),
            ], Response::HTTP_FORBIDDEN);
        }

        Cart::getCart()->shipping_method = $validatedData['shipping_method'];
        Cart::getCart()->save();

        $shippingAddress = Cart::getCart()?->shipping_address;

        if ($this->pickupPointService->isPickupMethod($validatedData['shipping_method'])) {
            $pickupPoint = $this->pickupPointService->requireActive($validatedData['pickup_point_id'] ?? null);

            $this->pickupPointService->assignToAddress($shippingAddress, $pickupPoint);
        } else {
            $this->pickupPointService->clearFromAddress($shippingAddress);
        }

        Cart::refreshCart();
        Cart::collectTotals();

        return response()->json([
            'payment_methods' => CheckoutStateResource::preferredPaymentMethods(
                Payment::getSupportedPaymentMethods()['payment_methods'] ?? []
            ),
        ]);
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

    protected function ensureDefaultPaymentMethod(): void
    {
        $cart = Cart::getCart();

        if (! $cart) {
            return;
        }

        $paymentMethods = CheckoutStateResource::preferredPaymentMethods(
            Payment::getSupportedPaymentMethods()['payment_methods'] ?? []
        );

        if ($paymentMethods === []) {
            return;
        }

        $availableMethods = collect($paymentMethods)->pluck('method')->all();
        $currentMethod = $cart->payment?->method;

        if ($currentMethod && in_array($currentMethod, $availableMethods, true)) {
            return;
        }

        $defaultMethod = CheckoutStateResource::defaultPaymentMethod($paymentMethods);

        if (! $defaultMethod) {
            return;
        }

        Cart::savePaymentMethod(['method' => $defaultMethod]);
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
