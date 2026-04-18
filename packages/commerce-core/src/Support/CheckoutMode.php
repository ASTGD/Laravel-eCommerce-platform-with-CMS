<?php

namespace Platform\CommerceCore\Support;

class CheckoutMode
{
    public const FULL = 'full';

    public const ONEPAGE = 'onepage';

    public function current(): string
    {
        $mode = core()->getConfigData('sales.checkout.shopping_cart.checkout_mode');

        return in_array($mode, [self::FULL, self::ONEPAGE], true)
            ? $mode
            : self::ONEPAGE;
    }

    public function usesCustomOnePage(): bool
    {
        return $this->current() === self::ONEPAGE;
    }

    public function entryRouteName(): string
    {
        return $this->usesCustomOnePage()
            ? 'shop.checkout.custom.index'
            : 'shop.checkout.onepage.index';
    }

    public function successRouteName(): string
    {
        return $this->usesCustomOnePage()
            ? 'shop.checkout.custom.success'
            : 'shop.checkout.onepage.success';
    }

    public function entryUrl(array $parameters = []): string
    {
        return route($this->entryRouteName(), $parameters);
    }

    public function successUrl(array $parameters = []): string
    {
        return route($this->successRouteName(), $parameters);
    }
}
