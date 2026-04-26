<?php

namespace Platform\CommerceCore\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Platform\CommerceCore\Console\Commands\ImportCodSettlementsCommand;
use Platform\CommerceCore\Console\Commands\ReconcilePendingPaymentsCommand;
use Platform\CommerceCore\Console\Commands\SyncShipmentTrackingCommand;
use Platform\CommerceCore\Contracts\DataSourceResolverContract;
use Platform\CommerceCore\Http\Controllers\Admin\RefundController as CommerceRefundController;
use Platform\CommerceCore\Http\Controllers\Admin\ShipmentController as CommerceShipmentController;
use Platform\CommerceCore\Http\Middleware\CaptureAffiliateReferral;
use Platform\CommerceCore\Http\Middleware\EnsureShippingModeAllowsFeature;
use Platform\CommerceCore\Http\Middleware\RedirectBasicShipmentBrowseRoutes;
use Platform\CommerceCore\Listeners\AttributeAffiliateOrder;
use Platform\CommerceCore\Listeners\Refund as CommerceRefundListener;
use Platform\CommerceCore\Listeners\ReverseAffiliateCommissionForCanceledOrder;
use Platform\CommerceCore\Listeners\SyncShipmentRecordFromNativeShipment;
use Platform\CommerceCore\Models\ShipmentCarrier;
use Platform\CommerceCore\Payment\PaymentManager;
use Platform\CommerceCore\Repositories\OrderRepository as CommerceOrderRepository;
use Platform\CommerceCore\Services\CheckoutGuestAccountService;
use Platform\CommerceCore\Services\DataSourceResolver;
use Platform\CommerceCore\Support\AdminMenu;
use Platform\CommerceCore\Support\CheckoutMode;
use Platform\CommerceCore\Support\ShippingMode;
use Webkul\Admin\Http\Controllers\Sales\RefundController as BaseRefundController;
use Webkul\Admin\Http\Controllers\Sales\ShipmentController as BaseShipmentController;
use Webkul\Admin\Listeners\Refund as BaseRefundListener;
use Webkul\Core\Http\Middleware\PreventRequestsDuringMaintenance;
use Webkul\Core\Menu as BaseMenu;
use Webkul\Payment\Payment as BasePaymentManager;
use Webkul\Sales\Repositories\OrderRepository as BaseOrderRepository;
use Webkul\Theme\ViewRenderEventManager;

class CommerceCoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DataSourceResolverContract::class, DataSourceResolver::class);
        $this->app->singleton(CheckoutMode::class);
        $this->app->singleton(ShippingMode::class);
        $this->app->bind(BaseMenu::class, AdminMenu::class);
        $this->app->bind(BasePaymentManager::class, PaymentManager::class);
        $this->app->bind(BaseShipmentController::class, CommerceShipmentController::class);
        $this->app->bind(BaseRefundController::class, CommerceRefundController::class);
        $this->app->bind(BaseRefundListener::class, CommerceRefundListener::class);
        $this->app->bind(BaseOrderRepository::class, CommerceOrderRepository::class);

        $this->mergeConfigFrom(__DIR__.'/../../config/system.php', 'core');
        $this->mergeConfigFrom(__DIR__.'/../../config/carriers.php', 'carriers');
        $this->mergeConfigFrom(__DIR__.'/../../config/carrier-booking.php', 'carrier_booking');
        $this->mergeConfigFrom(__DIR__.'/../../config/carrier-tracking.php', 'carrier_tracking');
        $this->mergeConfigFrom(__DIR__.'/../../config/affiliate.php', 'commerce_affiliate');
        $this->mergeConfigFrom(__DIR__.'/../../config/payment-methods.php', 'payment_methods');
        $this->mergeConfigFrom(__DIR__.'/../../config/menu.php', 'menu.admin');
        $this->mergeConfigFrom(__DIR__.'/../../config/customer-menu.php', 'menu.customer');
        $this->mergeConfigFrom(__DIR__.'/../../config/acl.php', 'acl');
    }

    public function boot(): void
    {
        $this->app['router']->aliasMiddleware('commerce.shipping-mode', EnsureShippingModeAllowsFeature::class);
        $this->app['router']->pushMiddlewareToGroup('web', CaptureAffiliateReferral::class);
        $this->app['router']->pushMiddlewareToGroup('admin', RedirectBasicShipmentBrowseRoutes::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                ImportCodSettlementsCommand::class,
                ReconcilePendingPaymentsCommand::class,
                SyncShipmentTrackingCommand::class,
            ]);
        }

        Route::middleware(['web', 'shop', PreventRequestsDuringMaintenance::class])
            ->group(__DIR__.'/../../routes/web.php');

        Route::middleware(['web', PreventRequestsDuringMaintenance::class])
            ->group(__DIR__.'/../../routes/webhooks.php');

        Route::middleware(['web', PreventRequestsDuringMaintenance::class])
            ->group(__DIR__.'/../../routes/admin.php');

        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'commerce-core');

        View::composer('admin::sales.shipments.create', static function ($view): void {
            $view->with('shipmentCarriers', ShipmentCarrier::query()
                ->active()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'tracking_url_template',
                ]));
        });

        Event::listen('bagisto.admin.sales.order.shipping-method.after', static function (ViewRenderEventManager $viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('commerce-core::admin.orders.pickup-point-details');
        });

        Event::listen('bagisto.admin.sales.order.payment-method.after', static function (ViewRenderEventManager $viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('commerce-core::admin.orders.payment-details');
        });

        Event::listen('bagisto.admin.sales.order.page_action.before', static function (ViewRenderEventManager $viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('commerce-core::admin.orders.confirm-button');
        });

        Event::listen('bagisto.admin.sales.order.right_component.after', static function (ViewRenderEventManager $viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('commerce-core::admin.orders.shipment-record-summary');
        });

        Event::listen('bagisto.shop.customers.account.orders.view.shipping_method_details.after', static function (ViewRenderEventManager $viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('commerce-core::shop.orders.pickup-point-details');
        });

        Event::listen('bagisto.shop.customers.account.orders.view.payment_method_details.after', static function (ViewRenderEventManager $viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('commerce-core::shop.orders.payment-details');
        });

        Event::listen('bagisto.shop.customers.account.orders.view.after', static function (ViewRenderEventManager $viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('commerce-core::shop.orders.shipment-tracking-timeline');
        });

        Event::listen('bagisto.shop.checkout.success.continue-shopping.after', static function (ViewRenderEventManager $viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('commerce-core::shop.partials.public-shipment-tracking-entry');
        });

        Event::listen('bagisto.shop.layout.footer.footer_text.after', static function (ViewRenderEventManager $viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('commerce-core::shop.partials.public-shipment-tracking-entry');
        });

        Event::listen('checkout.order.save.after', function ($order): void {
            app(CheckoutGuestAccountService::class)->attachExistingCustomerToOrder($order);
        });

        Event::listen('checkout.order.save.after', [AttributeAffiliateOrder::class, 'handle']);

        Event::listen('sales.order.cancel.after', [ReverseAffiliateCommissionForCanceledOrder::class, 'handle']);

        Event::listen('sales.shipment.save.after', [SyncShipmentRecordFromNativeShipment::class, 'handle']);

        Event::listen('customer.after.login', function ($customer): void {
            app(CheckoutGuestAccountService::class)->syncGuestOrdersForCustomer($customer);
        });
    }
}
