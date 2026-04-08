<?php

namespace Platform\CommerceCore\Enums;

enum DataSourceType: string
{
    case ManualProducts = 'manual_products';
    case CategoryProducts = 'category_products';
    case BrandProducts = 'brand_products';
    case AttributeProducts = 'attribute_products';
    case FeaturedProducts = 'featured_products';
    case BestSellers = 'best_sellers';
    case NewArrivals = 'new_arrivals';
    case DiscountedProducts = 'discounted_products';
    case ManualCategories = 'manual_categories';
    case SelectedContentEntries = 'selected_content_entries';
}
