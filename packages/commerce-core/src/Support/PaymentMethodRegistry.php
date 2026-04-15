<?php

namespace Platform\CommerceCore\Support;

final class PaymentMethodRegistry
{
    public const SSLCOMMERZ = 'sslcommerz';

    public const BKASH = 'bkash';

    public const LEGACY_SSLCOMMERZ_CODES = [
        'sslcommerz_card' => self::SSLCOMMERZ,
        'sslcommerz_bkash' => self::SSLCOMMERZ,
        'sslcommerz_nagad' => self::SSLCOMMERZ,
    ];

    public static function customStorefrontCodes(): array
    {
        return [
            self::SSLCOMMERZ,
            self::BKASH,
        ];
    }

    public static function canonicalCode(?string $code): ?string
    {
        if ($code === null || $code === '') {
            return $code;
        }

        return self::LEGACY_SSLCOMMERZ_CODES[$code] ?? $code;
    }

    public static function isHiddenLegacyCode(string $code): bool
    {
        return isset(self::LEGACY_SSLCOMMERZ_CODES[$code]);
    }

    public static function isSslCommerzFamily(?string $code): bool
    {
        return self::canonicalCode($code) === self::SSLCOMMERZ;
    }

    public static function labelForCode(?string $code): ?string
    {
        return match (self::canonicalCode($code)) {
            self::SSLCOMMERZ => 'SSLCommerz',
            self::BKASH => 'bKash',
            default => $code,
        };
    }
}
