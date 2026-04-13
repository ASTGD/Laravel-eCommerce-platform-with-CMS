<?php

namespace Platform\PlatformSupport\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Platform\PlatformSupport\Http\Middleware\AuthorizeAdminPermission;
use Platform\PlatformSupport\Repositories\AttributeOptionRepository;
use Platform\PlatformSupport\Services\SquareCanvasImageService;
use Webkul\Attribute\Repositories\AttributeOptionRepository as BaseAttributeOptionRepository;

class PlatformSupportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SquareCanvasImageService::class);
        $this->app->bind(BaseAttributeOptionRepository::class, AttributeOptionRepository::class);
    }

    public function boot(Router $router): void
    {
        config([
            'product_types.configurable.class' => \Platform\PlatformSupport\Product\Type\Configurable::class,
        ]);

        $router->aliasMiddleware('platform.acl', AuthorizeAdminPermission::class);

        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }
}
