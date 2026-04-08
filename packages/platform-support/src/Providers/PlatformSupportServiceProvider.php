<?php

declare(strict_types=1);

namespace PlatformSupport\Providers;

use Illuminate\Support\ServiceProvider;

class PlatformSupportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
    }
}
