<?php

namespace Platform\PlatformSupport\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Platform\PlatformSupport\Http\Middleware\AuthorizeAdminPermission;

class PlatformSupportServiceProvider extends ServiceProvider
{
    public function boot(Router $router): void
    {
        $router->aliasMiddleware('platform.acl', AuthorizeAdminPermission::class);

        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }
}
