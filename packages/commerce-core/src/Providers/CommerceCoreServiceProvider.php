<?php

namespace Platform\CommerceCore\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Platform\CommerceCore\Contracts\DataSourceResolverContract;
use Platform\CommerceCore\Http\Controllers\API\OnepageController as CommerceOnepageApiController;
use Platform\CommerceCore\Http\Controllers\OnepageController as CommerceOnepageController;
use Platform\CommerceCore\Payment\PaymentManager;
use Platform\CommerceCore\Services\DataSourceResolver;
use Webkul\Core\Http\Middleware\PreventRequestsDuringMaintenance;
use Webkul\Payment\Payment as BasePaymentManager;
use Webkul\Shop\Http\Controllers\API\OnepageController as BaseOnepageApiController;
use Webkul\Shop\Http\Controllers\OnepageController as BaseOnepageController;
use Webkul\Theme\ViewRenderEventManager;

class CommerceCoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DataSourceResolverContract::class, DataSourceResolver::class);
        $this->app->bind(BasePaymentManager::class, PaymentManager::class);
        $this->app->bind(BaseOnepageController::class, CommerceOnepageController::class);
        $this->app->bind(BaseOnepageApiController::class, CommerceOnepageApiController::class);

        $this->mergeConfigFrom(__DIR__.'/../../config/system.php', 'core');
        $this->mergeConfigFrom(__DIR__.'/../../config/carriers.php', 'carriers');
        $this->mergeConfigFrom(__DIR__.'/../../config/payment-methods.php', 'payment_methods');
        $this->mergeConfigFrom(__DIR__.'/../../config/menu.php', 'menu.admin');
        $this->mergeConfigFrom(__DIR__.'/../../config/acl.php', 'acl');
    }

    public function boot(): void
    {
        Route::middleware(['web', 'shop', PreventRequestsDuringMaintenance::class])
            ->group(__DIR__.'/../../routes/web.php');

        Route::middleware(['web', PreventRequestsDuringMaintenance::class])
            ->group(__DIR__.'/../../routes/admin.php');

        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'commerce-core');

        Event::listen('bagisto.admin.sales.order.shipping-method.after', static function (ViewRenderEventManager $viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('commerce-core::admin.orders.pickup-point-details');
        });

        Event::listen('bagisto.admin.sales.order.payment-method.after', static function (ViewRenderEventManager $viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('commerce-core::admin.orders.payment-details');
        });

        Event::listen('bagisto.shop.customers.account.orders.view.shipping_method_details.after', static function (ViewRenderEventManager $viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('commerce-core::shop.orders.pickup-point-details');
        });

        Event::listen('bagisto.shop.customers.account.orders.view.payment_method_details.after', static function (ViewRenderEventManager $viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('commerce-core::shop.orders.payment-details');
        });
    }
}
