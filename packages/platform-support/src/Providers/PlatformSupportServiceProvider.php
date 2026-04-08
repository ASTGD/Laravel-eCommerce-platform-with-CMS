<?php

namespace Platform\PlatformSupport\Providers;

use Illuminate\Support\ServiceProvider;

class PlatformSupportServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }
}
