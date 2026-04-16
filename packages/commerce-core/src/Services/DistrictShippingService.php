<?php

namespace Platform\CommerceCore\Services;

use Webkul\Checkout\Facades\Cart;
use Webkul\Shipping\Facades\Shipping;

class DistrictShippingService
{
    public const CARRIER_CODE = 'courier';

    /**
     * Collect shipping rates for the current cart.
     */
    public function collectRates(): array
    {
        $rates = Shipping::collectRates();

        return is_array($rates) ? ($rates['shippingMethods'] ?? []) : [];
    }

    /**
     * Collect district-based shipping rates and auto-select the courier rate for the cart.
     */
    public function applyAutomaticRate(): ?array
    {
        if (! $cart = Cart::getCart()) {
            return null;
        }

        $shippingMethods = $this->collectRates();

        $selectedRate = data_get($shippingMethods, self::CARRIER_CODE.'.rates.0');

        if (! $selectedRate) {
            return null;
        }

        $cart->shipping_method = $selectedRate->method;
        $cart->save();

        Cart::refreshCart();
        Cart::collectTotals();

        return $shippingMethods;
    }
}
