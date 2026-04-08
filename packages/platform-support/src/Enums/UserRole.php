<?php

declare(strict_types=1);

namespace PlatformSupport\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case CatalogManager = 'catalog_manager';
    case ContentManager = 'content_manager';
    case MarketingManager = 'marketing_manager';
    case OrderManager = 'order_manager';
    case SupportAdmin = 'support_admin';

    public function label(): string
    {
        return str($this->value)->replace('_', ' ')->title()->value();
    }
}
