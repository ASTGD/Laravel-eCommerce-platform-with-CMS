<?php

declare(strict_types=1);

namespace ThemeCore\Providers;

use Illuminate\Support\ServiceProvider;
use ThemeCore\Contracts\DataSourceResolverContract;
use ThemeCore\Contracts\SectionRendererContract;
use ThemeCore\Contracts\ThemePresetResolverContract;
use ThemeCore\Services\BladeSectionRenderer;
use ThemeCore\Services\DataSourceResolver;
use ThemeCore\Services\ThemePresetResolver;

class ThemeCoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DataSourceResolverContract::class, DataSourceResolver::class);
        $this->app->singleton(SectionRendererContract::class, BladeSectionRenderer::class);
        $this->app->singleton(ThemePresetResolverContract::class, ThemePresetResolver::class);
    }

    public function boot(): void
    {
        //
    }
}
