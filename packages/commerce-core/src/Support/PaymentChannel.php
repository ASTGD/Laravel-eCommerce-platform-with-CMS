<?php

namespace Platform\CommerceCore\Support;

final class PaymentChannel
{
    public const DEFAULT = 'default';

    public const CUSTOM = 'custom';

    public static function mode(): string
    {
        return core()->getConfigData('sales.payment_methods.mode.channel') ?: self::DEFAULT;
    }
}
