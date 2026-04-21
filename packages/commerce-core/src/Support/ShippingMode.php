<?php

namespace Platform\CommerceCore\Support;

use Illuminate\Support\Collection;
use Webkul\Core\Menu\MenuItem;

class ShippingMode
{
    public const MANUAL_BASIC = 'manual_basic';

    public const ADVANCED_PRO = 'advanced_pro';

    protected const ADVANCED_ONLY_ADMIN_PERMISSION_PREFIXES = [
        'sales.shipment_operations',
        'sales.cod_settlements',
        'sales.settlement_batches',
    ];

    protected const MANUAL_ONLY_ADMIN_PERMISSION_PREFIXES = [
        'sales.to_ship',
        'sales.shipped_orders',
        'sales.cod_receivables',
    ];

    public function current(): string
    {
        $mode = core()->getConfigData('sales.shipping_workflow.shipping_mode');

        return in_array($mode, [self::MANUAL_BASIC, self::ADVANCED_PRO], true)
            ? $mode
            : self::MANUAL_BASIC;
    }

    public function usesManualBasic(): bool
    {
        return $this->current() === self::MANUAL_BASIC;
    }

    public function usesAdvancedPro(): bool
    {
        return $this->current() === self::ADVANCED_PRO;
    }

    public function allowsAdminPermission(string $permission): bool
    {
        if ($this->matchesAdminKey($permission, self::ADVANCED_ONLY_ADMIN_PERMISSION_PREFIXES)) {
            return $this->usesAdvancedPro();
        }

        if ($this->matchesAdminKey($permission, self::MANUAL_ONLY_ADMIN_PERMISSION_PREFIXES)) {
            return $this->usesManualBasic();
        }

        return true;
    }

    public function showsAdvancedCarrierConfiguration(): bool
    {
        return $this->usesAdvancedPro();
    }

    public function filterAdminMenuItems(Collection $items): Collection
    {
        if ($this->usesAdvancedPro()) {
            return $items;
        }

        return $items
            ->map(fn (MenuItem $item) => $this->filterAdminMenuItem($item))
            ->filter()
            ->values();
    }

    protected function filterAdminMenuItem(MenuItem $item): ?MenuItem
    {
        if (! $this->allowsAdminPermission($item->getKey())) {
            return null;
        }

        if (! $item->haveChildren()) {
            return $item;
        }

        $children = $item->getChildren()
            ->map(fn (MenuItem $child) => $this->filterAdminMenuItem($child))
            ->filter()
            ->values();

        $item->children = $children;

        if ($children->isNotEmpty()) {
            $item->route = $children->first()->getRoute();
        }

        return $item;
    }

    protected function matchesAdminKey(string $key, array $prefixes): bool
    {
        foreach ($prefixes as $prefix) {
            if ($key === $prefix || str_starts_with($key, $prefix.'.')) {
                return true;
            }
        }

        return false;
    }
}
