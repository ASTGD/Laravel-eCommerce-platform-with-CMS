<?php

namespace Platform\CommerceCore\Http\Controllers\API;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Platform\CommerceCore\Services\CheckoutGuestAccountService;
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
        protected CheckoutGuestAccountService $checkoutGuestAccountService,
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
        $createAccount = $this->shouldCreateGuestAccount($params);

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

        $this->validateGuestAccountIntent($params, $createAccount);

        Cart::saveAddresses($params);

        $this->checkoutGuestAccountService->storeIntent(data_get($params, 'billing', []), $createAccount);

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

        try {
            $this->checkoutGuestAccountService->attachMatchingExistingCustomerToCart(Cart::getCart());
            $this->checkoutGuestAccountService->createCustomerFromIntent(Cart::getCart());
            $this->ensureDefaultPaymentMethod();
            Cart::collectTotals();
            $this->validateOrder();
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => collect($exception->errors())->flatten()->first() ?: 'Please review the checkout form.',
                'errors'  => $exception->errors(),
            ], 422);
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

        session()->put('order_id', $order->id);
        session()->flash('order_id', $order->id);

        return new JsonResource([
            'redirect' => true,
            'redirect_url' => route('shop.checkout.success', ['order' => $order->id]),
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
        Cart::refreshCart();
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

    protected function shouldCreateGuestAccount(array $params): bool
    {
        if (auth()->guard('customer')->check()) {
            return false;
        }

        return filter_var(data_get($params, 'billing.create_account'), FILTER_VALIDATE_BOOL);
    }

    protected function validateGuestAccountIntent(array $params, bool $createAccount): void
    {
        if (! $createAccount) {
            return;
        }

        Validator::make($params, [
            'billing.email' => 'required|email|unique:customers,email,NULL,id,channel_id,'.core()->getCurrentChannel()->id,
            'billing.phone' => 'required|unique:customers,phone',
            'billing.password' => 'required|confirmed|min:6',
        ], [
            'billing.password.confirmed' => 'The password confirmation does not match.',
            'billing.phone.unique' => 'This phone number is already registered. Please sign in instead.',
        ])->validate();
    }
}
