<?php

namespace Platform\CommerceCore\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\View\View;
use Platform\CommerceCore\Services\PickupPointService;
use Webkul\Checkout\Facades\Cart;
use Webkul\Shop\Http\Controllers\OnepageController as BaseOnepageController;

class OnepageController extends BaseOnepageController
{
    public function __construct(protected PickupPointService $pickupPointService) {}

    public function index()
    {
        if (! core()->getConfigData('sales.checkout.shopping_cart.cart_page')) {
            abort(404);
        }

        Event::dispatch('checkout.load.index');

        if (
            ! auth()->guard('customer')->check()
            && ! core()->getConfigData('sales.checkout.shopping_cart.allow_guest_checkout')
        ) {
            return redirect()->route('shop.customer.session.index');
        }

        if (auth()->guard('customer')->user()?->is_suspended) {
            session()->flash('warning', trans('shop::app.checkout.cart.suspended-account-message'));

            return redirect()->route('shop.checkout.cart.index');
        }

        if (Cart::hasError()) {
            return redirect()->route('shop.checkout.cart.index');
        }

        $cart = Cart::getCart();

        if (
            ! auth()->guard('customer')->check()
            && (
                $cart->hasDownloadableItems()
                || ! $cart->hasGuestCheckoutItems()
            )
        ) {
            return redirect()->route('shop.customer.session.index');
        }

        return view('shop::checkout.onepage.index', [
            'cart' => $cart,
            'pickupPoints' => $this->pickupPointService->checkoutOptions()->values()->all(),
        ]);
    }
}
