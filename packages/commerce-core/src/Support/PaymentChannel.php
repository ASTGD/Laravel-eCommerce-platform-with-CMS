<?php

namespace Platform\CommerceCore\Support;

final class PaymentChannel
{
    public const DEFAULT = 'default';

    public const CUSTOM = 'custom';

    public const CUSTOM_METHOD_CODES = [
        'sslcommerz_card',
        'sslcommerz_bkash',
        'sslcommerz_nagad',
    ];

    public static function mode(): string
    {
        return core()->getConfigData('sales.payment_methods.mode.channel') ?: self::DEFAULT;
    }
}
