<?php

namespace Platform\CommerceCore\Shipping\Carriers;

use Illuminate\Support\Str;
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

        $isDhakaDistrict = $this->isDhakaDistrict($shippingAddress->state);

        $rateTitle = $this->getConfigData($isDhakaDistrict ? 'dhaka_title' : 'outside_dhaka_title')
            ?: ($isDhakaDistrict ? 'Dhaka Delivery' : 'Outside Dhaka Delivery');

        $rateDescription = $this->getConfigData('description')
            ?: ($isDhakaDistrict ? 'Delivery charge for Dhaka district' : 'Delivery charge for outside Dhaka districts');

        $rate = $this->makeRate(
            $isDhakaDistrict ? 'dhaka' : 'outside_dhaka',
            $rateTitle,
            $rateDescription,
            (float) $this->getConfigData($isDhakaDistrict ? 'dhaka_rate' : 'outside_dhaka_rate')
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

    protected function isDhakaDistrict(string $district): bool
    {
        $normalizedDistrict = Str::lower(trim($district));
        $configuredDistrict = Str::lower(trim((string) ($this->getConfigData('dhaka_district') ?: 'Dhaka')));

        return $normalizedDistrict !== '' && $normalizedDistrict === $configuredDistrict;
    }
}
