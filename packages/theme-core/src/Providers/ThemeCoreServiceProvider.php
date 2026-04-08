<?php

namespace Platform\ThemeCore\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Platform\ThemeCore\Contracts\ComponentRendererContract;
use Platform\ThemeCore\Contracts\SectionRendererContract;
use Platform\ThemeCore\Contracts\ThemePresetResolverContract;
use Platform\ThemeCore\Services\BladeComponentRenderer;
use Platform\ThemeCore\Services\BladeSectionRenderer;
use Platform\ThemeCore\Services\ThemePresetResolver;
use Webkul\Core\Http\Middleware\PreventRequestsDuringMaintenance;

class ThemeCoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/menu.php', 'menu.admin');
        $this->mergeConfigFrom(__DIR__.'/../../config/acl.php', 'acl');

        $this->app->singleton(SectionRendererContract::class, BladeSectionRenderer::class);
        $this->app->singleton(ComponentRendererContract::class, BladeComponentRenderer::class);
        $this->app->singleton(ThemePresetResolverContract::class, ThemePresetResolver::class);
    }

    public function boot(): void
    {
        Route::middleware(['web', PreventRequestsDuringMaintenance::class])
            ->group(__DIR__.'/../../routes/admin.php');

        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'theme-core');
    }
}
