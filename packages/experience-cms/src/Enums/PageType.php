<?php

declare(strict_types=1);

namespace ExperienceCms\Enums;

enum PageType: string
{
    case Homepage = 'homepage';
    case StaticPage = 'static_page';
    case CategoryListing = 'category_listing_page';
    case ProductDetail = 'product_detail_page';
    case Campaign = 'campaign_page';
}
