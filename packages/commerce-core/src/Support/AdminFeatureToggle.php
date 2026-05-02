<?php

namespace Platform\CommerceCore\Support;

class AdminFeatureToggle
{
    public const BOOKING = 'booking';

    public const PRODUCT_RETURN = 'product_return';

    public const MARKETING = 'marketing';

    public const CUSTOMER_REVIEWS = 'customer_reviews';

    public const GDPR_DATA_REQUESTS = 'gdpr_data_requests';

    protected const CONFIG_PATHS = [
        self::BOOKING => 'general.admin_modules.visibility.booking_enabled',
        self::PRODUCT_RETURN => 'general.admin_modules.visibility.product_return_enabled',
        self::MARKETING => 'general.admin_modules.visibility.marketing_enabled',
        self::CUSTOMER_REVIEWS => 'general.admin_modules.visibility.customer_reviews_enabled',
        self::GDPR_DATA_REQUESTS => 'general.admin_modules.visibility.gdpr_data_requests_enabled',
    ];

    protected const MENU_PREFIXES = [
        'sales.bookings' => self::BOOKING,
        'sales.rma' => self::PRODUCT_RETURN,
        'marketing' => self::MARKETING,
        'customers.reviews' => self::CUSTOMER_REVIEWS,
        'customers.gdpr_requests' => self::GDPR_DATA_REQUESTS,
        'account.reviews' => self::CUSTOMER_REVIEWS,
        'account.gdpr_data_request' => self::GDPR_DATA_REQUESTS,
    ];

    protected const ROUTE_PREFIXES = [
        'admin.sales.bookings.' => self::BOOKING,
        'admin.sales.rma.' => self::PRODUCT_RETURN,
        'admin.marketing.' => self::MARKETING,
        'admin.customers.customers.review.' => self::CUSTOMER_REVIEWS,
        'admin.customers.gdpr.' => self::GDPR_DATA_REQUESTS,
        'shop.customers.account.reviews.' => self::CUSTOMER_REVIEWS,
        'shop.customers.account.gdpr.' => self::GDPR_DATA_REQUESTS,
    ];

    public function enabled(string $feature): bool
    {
        $path = self::CONFIG_PATHS[$feature] ?? null;

        if (! $path) {
            return true;
        }

        return $this->booleanValue(core()->getConfigData($path));
    }

    public function allowsMenuKey(string $menuKey): bool
    {
        $feature = $this->featureForMenuKey($menuKey);

        return $feature ? $this->enabled($feature) : true;
    }

    public function featureForRouteName(?string $routeName): ?string
    {
        if (! $routeName) {
            return null;
        }

        foreach (self::ROUTE_PREFIXES as $prefix => $feature) {
            if (str_starts_with($routeName, $prefix)) {
                return $feature;
            }
        }

        return null;
    }

    protected function featureForMenuKey(string $menuKey): ?string
    {
        foreach (self::MENU_PREFIXES as $prefix => $feature) {
            if ($menuKey === $prefix || str_starts_with($menuKey, $prefix.'.')) {
                return $feature;
            }
        }

        return null;
    }

    protected function booleanValue(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 'true', 'on', 'yes'], true);
    }
}
