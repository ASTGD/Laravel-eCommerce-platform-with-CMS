<?php

namespace Platform\ExperienceCms\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Platform\ExperienceCms\ComponentTypes\BadgeListComponentType;
use Platform\ExperienceCms\ComponentTypes\BodyTextComponentType;
use Platform\ExperienceCms\ComponentTypes\CtaButtonGroupComponentType;
use Platform\ExperienceCms\ComponentTypes\HeadlineComponentType;
use Platform\ExperienceCms\ComponentTypes\LinkListComponentType;
use Platform\ExperienceCms\Contracts\CategoryPagePayloadBuilderContract;
use Platform\ExperienceCms\Contracts\ContentEntryResolverContract;
use Platform\ExperienceCms\Contracts\FooterResolverContract;
use Platform\ExperienceCms\Contracts\HeaderResolverContract;
use Platform\ExperienceCms\Contracts\MenuResolverContract;
use Platform\ExperienceCms\Contracts\PageAssignmentResolverContract;
use Platform\ExperienceCms\Contracts\PagePreviewServiceContract;
use Platform\ExperienceCms\Contracts\PageVersionRestoreContract;
use Platform\ExperienceCms\Contracts\ProductPagePayloadBuilderContract;
use Platform\ExperienceCms\Contracts\PublishWorkflowContract;
use Platform\ExperienceCms\Contracts\SiteSettingsResolverContract;
use Platform\ExperienceCms\Http\Controllers\CmsAwareHomeController;
use Platform\ExperienceCms\Http\Controllers\CmsAwareProductsCategoriesProxyController;
use Platform\ExperienceCms\SectionTypes\AddToCartSectionType;
use Platform\ExperienceCms\SectionTypes\BestSellersSectionType;
use Platform\ExperienceCms\SectionTypes\CategoryGridSectionType;
use Platform\ExperienceCms\SectionTypes\CategoryIntroSectionType;
use Platform\ExperienceCms\SectionTypes\FaqBlockSectionType;
use Platform\ExperienceCms\SectionTypes\FeaturedProductsSectionType;
use Platform\ExperienceCms\SectionTypes\FlashSaleProductsSectionType;
use Platform\ExperienceCms\SectionTypes\HeroBannerSectionType;
use Platform\ExperienceCms\SectionTypes\NewArrivalsSectionType;
use Platform\ExperienceCms\SectionTypes\PromoStripSectionType;
use Platform\ExperienceCms\SectionTypes\ProductDetailsSectionType;
use Platform\ExperienceCms\SectionTypes\ProductGallerySectionType;
use Platform\ExperienceCms\SectionTypes\ProductOptionsSectionType;
use Platform\ExperienceCms\SectionTypes\ProductPriceSectionType;
use Platform\ExperienceCms\SectionTypes\ProductSummarySectionType;
use Platform\ExperienceCms\SectionTypes\RelatedProductsSectionType;
use Platform\ExperienceCms\SectionTypes\RichTextSectionType;
use Platform\ExperienceCms\SectionTypes\StockShippingInfoSectionType;
use Platform\ExperienceCms\SectionTypes\TrustBadgesSectionType;
use Platform\ExperienceCms\Services\CategoryPagePayloadBuilder;
use Platform\ExperienceCms\Services\ComponentTypeRegistry;
use Platform\ExperienceCms\Services\ContentEntryResolver;
use Platform\ExperienceCms\Services\FooterResolver;
use Platform\ExperienceCms\Services\HeaderResolver;
use Platform\ExperienceCms\Services\MenuResolver;
use Platform\ExperienceCms\Services\PageAssignmentResolver;
use Platform\ExperienceCms\Services\PagePreviewService;
use Platform\ExperienceCms\Services\PageVersionRestoreService;
use Platform\ExperienceCms\Services\ProductPagePayloadBuilder;
use Platform\ExperienceCms\Services\PublishWorkflow;
use Platform\ExperienceCms\Services\SectionTypeRegistry;
use Platform\ExperienceCms\Services\SiteSettingsResolver;
use Webkul\Core\Http\Middleware\PreventRequestsDuringMaintenance;
use Webkul\Shop\Http\Controllers\HomeController;
use Webkul\Shop\Http\Controllers\ProductsCategoriesProxyController;

class ExperienceCmsServiceProvider extends ServiceProvider
{
    private function usesCmsStorefront(): bool
    {
        return config('experience-cms.storefront_mode') === 'cms';
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/menu.php', 'menu.admin');
        $this->mergeConfigFrom(__DIR__.'/../../config/acl.php', 'acl');
        $this->mergeConfigFrom(__DIR__.'/../../config/experience-cms.php', 'experience-cms');

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
                new CategoryIntroSectionType(),
                new ProductGallerySectionType(),
                new ProductSummarySectionType(),
                new ProductPriceSectionType(),
                new ProductOptionsSectionType(),
                new AddToCartSectionType(),
                new StockShippingInfoSectionType(),
                new ProductDetailsSectionType(),
                new FaqBlockSectionType(),
                new RelatedProductsSectionType(),
                new TrustBadgesSectionType(),
            ]);
        });

        $this->app->singleton(ComponentTypeRegistry::class, fn () => new ComponentTypeRegistry([
            new HeadlineComponentType(),
            new BodyTextComponentType(),
            new CtaButtonGroupComponentType(),
            new BadgeListComponentType(),
            new LinkListComponentType(),
        ]));

        $this->app->bind(PagePreviewServiceContract::class, PagePreviewService::class);
        $this->app->bind(CategoryPagePayloadBuilderContract::class, CategoryPagePayloadBuilder::class);
        $this->app->bind(ProductPagePayloadBuilderContract::class, ProductPagePayloadBuilder::class);
        $this->app->bind(PublishWorkflowContract::class, PublishWorkflow::class);
        $this->app->bind(PageVersionRestoreContract::class, PageVersionRestoreService::class);
        $this->app->bind(PageAssignmentResolverContract::class, PageAssignmentResolver::class);
        $this->app->bind(ContentEntryResolverContract::class, ContentEntryResolver::class);
        $this->app->bind(SiteSettingsResolverContract::class, SiteSettingsResolver::class);
        $this->app->bind(MenuResolverContract::class, MenuResolver::class);
        $this->app->bind(HeaderResolverContract::class, HeaderResolver::class);
        $this->app->bind(FooterResolverContract::class, FooterResolver::class);

        if ($this->usesCmsStorefront()) {
            $this->app->bind(HomeController::class, CmsAwareHomeController::class);
            $this->app->bind(ProductsCategoriesProxyController::class, CmsAwareProductsCategoriesProxyController::class);
        }
    }

    public function boot(): void
    {
        Route::middleware(['web', PreventRequestsDuringMaintenance::class])
            ->group(__DIR__.'/../../routes/admin.php');

        if ($this->usesCmsStorefront()) {
            Route::middleware(['web', PreventRequestsDuringMaintenance::class])
                ->group(__DIR__.'/../../routes/storefront.php');
        }

        Route::getRoutes()->refreshNameLookups();
        Route::getRoutes()->refreshActionLookups();

        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'experience-cms');
    }
}
