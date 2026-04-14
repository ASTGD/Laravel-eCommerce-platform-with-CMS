<?php

namespace Platform\CommerceCore\Shipping\Carriers;

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

        $rates = [];

        if ($this->getConfigData('home_delivery_active')) {
            $rates[] = $this->makeRate(
                'home_delivery',
                $this->getConfigData('home_delivery_title') ?: 'Home Delivery',
                $this->getConfigData('description') ?: 'Courier home delivery',
                (float) $this->getConfigData('home_delivery_rate')
            );
        }

        if ($this->getConfigData('pickup_active')) {
            $rates[] = $this->makeRate(
                'pickup',
                $this->getConfigData('pickup_title') ?: 'Courier Pick-up',
                $this->getConfigData('description') ?: 'Courier pick-up',
                (float) $this->getConfigData('pickup_rate')
            );
        }

        return $rates ?: false;
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
