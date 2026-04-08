<?php

declare(strict_types=1);

namespace App\Support;

class AdminNavigation
{
    public static function groups(): array
    {
        return [
            'Dashboard' => [
                ['label' => 'Overview', 'route' => 'admin.dashboard', 'implemented' => true],
            ],
            'Catalog' => [
                ['label' => 'Products', 'implemented' => false],
                ['label' => 'Categories', 'implemented' => false],
                ['label' => 'Attributes', 'implemented' => false],
                ['label' => 'Inventory', 'implemented' => false],
            ],
            'Sales' => [
                ['label' => 'Orders', 'implemented' => false],
                ['label' => 'Transactions', 'implemented' => false],
                ['label' => 'Shipments', 'implemented' => false],
                ['label' => 'Refunds', 'implemented' => false],
                ['label' => 'Customers', 'implemented' => false],
            ],
            'Promotions' => [
                ['label' => 'Coupons', 'implemented' => false],
                ['label' => 'Campaigns', 'implemented' => false],
            ],
            'CMS' => [
                ['label' => 'Pages', 'route' => 'admin.pages.index', 'implemented' => true],
                ['label' => 'Templates', 'route' => 'admin.templates.index', 'implemented' => true],
                ['label' => 'Sections', 'route' => 'admin.section-types.index', 'implemented' => true],
                ['label' => 'Components', 'implemented' => false],
                ['label' => 'Menus', 'route' => 'admin.menus.index', 'implemented' => true],
                ['label' => 'Header', 'implemented' => false],
                ['label' => 'Footer', 'implemented' => false],
                ['label' => 'Content Entries', 'implemented' => false],
            ],
            'Theme' => [
                ['label' => 'Presets', 'route' => 'admin.theme-presets.index', 'implemented' => true],
                ['label' => 'Global Settings', 'implemented' => false],
                ['label' => 'Style Tokens', 'implemented' => false],
            ],
            'SEO' => [
                ['label' => 'Meta Defaults', 'implemented' => false],
                ['label' => 'Redirects', 'implemented' => false],
                ['label' => 'URL Rewrites', 'implemented' => false],
            ],
            'Media' => [
                ['label' => 'Media Library', 'implemented' => false],
            ],
            'Settings' => [
                ['label' => 'Users & Roles', 'implemented' => false],
                ['label' => 'Audit Logs', 'implemented' => false],
            ],
        ];
    }
}
