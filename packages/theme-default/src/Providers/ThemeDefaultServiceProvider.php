<?php

namespace Platform\ThemeDefault\Providers;

use Illuminate\Support\ServiceProvider;

class ThemeDefaultServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'theme-default');
    }
}
