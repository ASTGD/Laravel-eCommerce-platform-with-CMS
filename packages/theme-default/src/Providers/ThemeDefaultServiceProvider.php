<?php

declare(strict_types=1);

namespace ThemeDefault\Providers;

use Illuminate\Support\ServiceProvider;

class ThemeDefaultServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'theme-default');
    }
}
