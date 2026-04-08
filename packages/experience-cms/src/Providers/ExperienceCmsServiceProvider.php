<?php

namespace Platform\ExperienceCms\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Platform\ExperienceCms\Contracts\ComponentTypeContract;
use Platform\ExperienceCms\Contracts\FooterResolverContract;
use Platform\ExperienceCms\Contracts\HeaderResolverContract;
use Platform\ExperienceCms\Contracts\MenuResolverContract;
use Platform\ExperienceCms\Contracts\PagePreviewServiceContract;
use Platform\ExperienceCms\Contracts\PublishWorkflowContract;
use Platform\ExperienceCms\SectionTypes\BestSellersSectionType;
use Platform\ExperienceCms\SectionTypes\CategoryGridSectionType;
use Platform\ExperienceCms\SectionTypes\FeaturedProductsSectionType;
use Platform\ExperienceCms\SectionTypes\FlashSaleProductsSectionType;
use Platform\ExperienceCms\SectionTypes\HeroBannerSectionType;
use Platform\ExperienceCms\SectionTypes\NewArrivalsSectionType;
use Platform\ExperienceCms\SectionTypes\PromoStripSectionType;
use Platform\ExperienceCms\SectionTypes\RichTextSectionType;
use Platform\ExperienceCms\Services\ComponentTypeRegistry;
use Platform\ExperienceCms\Services\FooterResolver;
use Platform\ExperienceCms\Services\HeaderResolver;
use Platform\ExperienceCms\Services\MenuResolver;
use Platform\ExperienceCms\Services\PagePreviewService;
use Platform\ExperienceCms\Services\PublishWorkflow;
use Platform\ExperienceCms\Services\SectionTypeRegistry;
use Webkul\Core\Http\Middleware\PreventRequestsDuringMaintenance;

class ExperienceCmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/menu.php', 'menu.admin');
        $this->mergeConfigFrom(__DIR__.'/../../config/acl.php', 'acl');

        $this->app->singleton(SectionTypeRegistry::class, function () {
            return new SectionTypeRegistry([
                new HeroBannerSectionType(),
                new PromoStripSectionType(),
                new CategoryGridSectionType(),
                new FeaturedProductsSectionType(),
                new FlashSaleProductsSectionType(),
                new BestSellersSectionType(),
                new NewArrivalsSectionType(),
                new RichTextSectionType(),
            ]);
        });

        $this->app->singleton(ComponentTypeRegistry::class, fn () => new ComponentTypeRegistry([]));

        $this->app->bind(PagePreviewServiceContract::class, PagePreviewService::class);
        $this->app->bind(PublishWorkflowContract::class, PublishWorkflow::class);
        $this->app->bind(MenuResolverContract::class, MenuResolver::class);
        $this->app->bind(HeaderResolverContract::class, HeaderResolver::class);
        $this->app->bind(FooterResolverContract::class, FooterResolver::class);
    }

    public function boot(): void
    {
        Route::middleware(['web', PreventRequestsDuringMaintenance::class])
            ->group(__DIR__.'/../../routes/admin.php');

        Route::middleware(['web', PreventRequestsDuringMaintenance::class])
            ->group(__DIR__.'/../../routes/storefront.php');

        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'experience-cms');
    }
}
