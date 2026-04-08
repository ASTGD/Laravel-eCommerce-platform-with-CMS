<?php

namespace Platform\SeoTools\Providers;

use Illuminate\Support\ServiceProvider;

class SeoToolsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }
}
