<?php

declare(strict_types=1);

namespace ExperienceCms\Providers;

use ExperienceCms\Models\Menu;
use ExperienceCms\Models\MenuItem;
use ExperienceCms\Models\Page;
use ExperienceCms\Models\PageSection;
use ExperienceCms\Models\SectionType;
use ExperienceCms\Models\Template;
use ExperienceCms\Models\ThemePreset;
use ExperienceCms\SectionTypes\BestSellersSectionType;
use ExperienceCms\SectionTypes\CategoryGridSectionType;
use ExperienceCms\SectionTypes\FeaturedProductsSectionType;
use ExperienceCms\SectionTypes\FlashSaleProductsSectionType;
use ExperienceCms\SectionTypes\HeroBannerSectionType;
use ExperienceCms\SectionTypes\NewArrivalsSectionType;
use ExperienceCms\SectionTypes\PromoStripSectionType;
use ExperienceCms\SectionTypes\RichTextSectionType;
use ExperienceCms\Services\SectionRegistry;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ExperienceCmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SectionRegistry::class, function (): SectionRegistry {
            return new SectionRegistry([
                new HeroBannerSectionType,
                new PromoStripSectionType,
                new CategoryGridSectionType,
                new FeaturedProductsSectionType,
                new FlashSaleProductsSectionType,
                new BestSellersSectionType,
                new NewArrivalsSectionType,
                new RichTextSectionType,
            ]);
        });
    }

    public function boot(): void
    {
        Route::model('page', Page::class);
        Route::model('section', PageSection::class);
        Route::model('template', Template::class);
        Route::model('section_type', SectionType::class);
        Route::model('theme_preset', ThemePreset::class);
        Route::model('menu', Menu::class);
        Route::model('item', MenuItem::class);

        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../../routes/admin.php');
        $this->loadRoutesFrom(__DIR__.'/../../routes/storefront.php');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'experience-cms');
    }
}
