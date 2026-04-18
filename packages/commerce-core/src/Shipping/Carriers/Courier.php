<?php

namespace Platform\CommerceCore\Shipping\Carriers;

use Platform\CommerceCore\Services\BangladeshDistrictService;
use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Models\CartShippingRate;
use Webkul\Shipping\Carriers\AbstractShipping;

class Courier extends AbstractShipping
{
    protected $code = 'courier';

    public function calculate()
    {
        if (! $this->isAvailable()) {
            return false;
        }

        $shippingAddress = Cart::getCart()?->shipping_address;

        if (! $shippingAddress || blank($shippingAddress->state)) {
            return false;
        }

        $districtService = app(BangladeshDistrictService::class);
        $districtName = $districtService->resolveDistrictName($shippingAddress->state);

        $rate = $this->makeRate(
            str($districtName !== '' ? $districtName : 'delivery')->slug('_'),
            $districtService->resolveTitle($districtName),
            $districtService->resolveDescription($districtName),
            $districtService->resolveRate($districtName)
        );

        return [$rate];
    }

    protected function makeRate(string $suffix, string $title, string $description, float $basePrice): CartShippingRate
    {
        $rate = new CartShippingRate;

        $rate->carrier = $this->getCode();
        $rate->carrier_title = $this->getConfigData('title') ?: 'Courier';
        $rate->method = sprintf('%s_%s', $this->getCode(), $suffix);
        $rate->method_title = $title;
        $rate->method_description = $description;
        $rate->price = core()->convertPrice($basePrice);
        $rate->base_price = $basePrice;

        return $rate;
    }
}
