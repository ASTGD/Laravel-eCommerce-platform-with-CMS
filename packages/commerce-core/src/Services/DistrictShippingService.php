<?php

namespace Platform\CommerceCore\Services;

use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Models\CartShippingRate;
use Webkul\Shipping\Facades\Shipping;

class DistrictShippingService
{
    public const CARRIER_CODE = 'courier';

    public function __construct(protected BangladeshDistrictService $districtService) {}

    /**
     * Collect shipping rates for the current cart.
     */
    public function collectRates(): array
    {
        $rates = Shipping::collectRates();

        $shippingMethods = is_array($rates) ? ($rates['shippingMethods'] ?? []) : [];

        if (data_get($shippingMethods, self::CARRIER_CODE.'.rates.0')) {
            return $shippingMethods;
        }

        if (! $fallbackRate = $this->createFallbackRate()) {
            return $shippingMethods;
        }

        $shippingMethods[self::CARRIER_CODE] = [
            'carrier_title' => $fallbackRate->carrier_title,
            'rates' => [$fallbackRate],
        ];

        return $shippingMethods;
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

    protected function createFallbackRate(): ?CartShippingRate
    {
        $cart = Cart::getCart();
        $shippingAddress = $cart?->shipping_address;

        if (! $cart || ! $shippingAddress || blank($shippingAddress->state)) {
            return null;
        }

        $districtName = $this->districtService->resolveDistrictName($shippingAddress->state);
        $basePrice = $this->districtService->resolveRate($districtName);

        $rate = new CartShippingRate;

        $rate->carrier = self::CARRIER_CODE;
        $rate->carrier_title = core()->getConfigData('sales.carriers.courier.title') ?: 'Courier';
        $rate->method = sprintf('%s_%s', self::CARRIER_CODE, str($districtName !== '' ? $districtName : 'delivery')->slug('_'));
        $rate->method_title = $this->districtService->resolveTitle($districtName);
        $rate->method_description = $this->districtService->resolveDescription($districtName);
        $rate->price = core()->convertPrice($basePrice);
        $rate->base_price = $basePrice;
        $rate->price_incl_tax = $rate->price;
        $rate->base_price_incl_tax = $basePrice;
        $rate->cart_id = $cart->id;
        $rate->cart_address_id = $shippingAddress->id;
        $rate->save();

        return $rate;
    }
}
