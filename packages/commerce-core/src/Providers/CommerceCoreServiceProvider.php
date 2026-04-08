<?php

namespace Platform\CommerceCore\Providers;

use Illuminate\Support\ServiceProvider;
use Platform\CommerceCore\Contracts\DataSourceResolverContract;
use Platform\CommerceCore\Services\DataSourceResolver;

class CommerceCoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DataSourceResolverContract::class, DataSourceResolver::class);
    }
}
