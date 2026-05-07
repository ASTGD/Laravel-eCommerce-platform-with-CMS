<?php

namespace Platform\ExperienceCms\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Platform\ExperienceCms\Models\ContentEntry;
use Platform\ExperienceCms\Models\FooterConfig;
use Platform\ExperienceCms\Models\HeaderConfig;
use Platform\ExperienceCms\Models\Menu;
use Platform\ExperienceCms\Models\Page;
use Platform\ExperienceCms\Models\SiteSetting;
use Platform\ThemeCore\Models\ThemePreset;

class DashboardController extends Controller
{
    public function index(): View
    {
        $homepage = $this->firstModel(Page::class, fn ($query) => $query->where('slug', 'home'));

        $activeThemePreset = $this->firstModel(ThemePreset::class, fn ($query) => $query->where('is_default', true))
            ?? $this->firstModel(ThemePreset::class, fn ($query) => $query
                ->where('is_active', true)
                ->orderBy('name'));

        $activeHeaderConfig = $this->firstModel(HeaderConfig::class, fn ($query) => $query->where('is_default', true))
            ?? $this->firstModel(HeaderConfig::class, fn ($query) => $query->orderBy('code'));

        $activeFooterConfig = $this->firstModel(FooterConfig::class, fn ($query) => $query->where('is_default', true))
            ?? $this->firstModel(FooterConfig::class, fn ($query) => $query->orderBy('code'));

        $activeMenu = $this->firstModel(Menu::class, fn ($query) => $query
            ->where('is_active', true)
            ->orderBy('name'));

        $siteSettings = $this->firstModel(SiteSetting::class, fn ($query) => $query->orderBy('key'));

        $homepageStatus = $homepage
            ? ($homepage->isPublished() ? 'published' : 'draft')
            : 'missing';
        $hasTheme = (bool) $activeThemePreset;
        $hasHeader = (bool) $activeHeaderConfig;
        $hasFooter = (bool) $activeFooterConfig;
        $hasMenu = (bool) $activeMenu;
        $hasSiteSettings = (bool) $siteSettings;
        $shopThemeCode = core()->getCurrentChannel()?->theme ?: config('themes.shop-default');

        $overview = [
            'public_site' => [
                'label' => $shopThemeCode === config('themes.shop-default') ? 'Default' : ucfirst((string) $shopThemeCode),
                'description' => 'Public storefront routes use the active Bagisto channel theme.',
            ],
            'active_theme' => [
                'name' => $activeThemePreset?->name,
                'code' => $activeThemePreset?->code,
            ],
            'pages' => [
                'total' => $this->countModel(Page::class),
                'published' => $this->countModel(Page::class, fn ($query) => $query->where('status', Page::STATUS_PUBLISHED)),
                'draft' => $this->countModel(Page::class, fn ($query) => $query->where('status', Page::STATUS_DRAFT)),
            ],
            'content_entries' => $this->countModel(ContentEntry::class),
            'menus' => $this->countModel(Menu::class),
            'header_configs' => $this->countModel(HeaderConfig::class),
            'footer_configs' => $this->countModel(FooterConfig::class),
            'site_settings' => $this->countModel(SiteSetting::class),
            'homepage' => [
                'label' => $homepage?->title ?? 'Homepage',
                'status' => $homepageStatus,
            ],
            'setup' => [
                'has_theme' => $hasTheme,
                'has_header' => $hasHeader,
                'has_footer' => $hasFooter,
                'has_menu' => $hasMenu,
                'has_site_settings' => $hasSiteSettings,
                'is_complete' => $hasHeader && $hasFooter && $hasMenu && $hasSiteSettings,
            ],
        ];

        $urls = [
            'preview' => $this->routeUrl('shop.home.index'),
            'cms' => $this->routeUrl('admin.cms.index', 'admin.cms.dashboard.index'),
            'themes' => $this->routeUrl('admin.theme.presets.index', 'admin.cms.dashboard.index'),
            'settings' => $this->routeUrl('admin.cms.settings.index', 'admin.cms.index'),
            'header_footer' => $this->routeUrl('admin.cms.index', 'admin.cms.dashboard.index'),
        ];

        return view('experience-cms::admin.dashboard.index', [
            'overview' => $overview,
            'urls' => $urls,
        ]);
    }

    private function countModel(string $modelClass, ?Closure $queryCallback = null): int
    {
        if (! $this->modelIsQueryable($modelClass)) {
            return 0;
        }

        $query = $modelClass::query();

        if ($queryCallback) {
            $queryCallback($query);
        }

        return $query->count();
    }

    private function firstModel(string $modelClass, ?Closure $queryCallback = null): ?Model
    {
        if (! $this->modelIsQueryable($modelClass)) {
            return null;
        }

        $query = $modelClass::query();

        if ($queryCallback) {
            $queryCallback($query);
        }

        return $query->first();
    }

    private function modelIsQueryable(string $modelClass): bool
    {
        if (! class_exists($modelClass)) {
            return false;
        }

        $model = new $modelClass;

        return $model instanceof Model
            && Schema::hasTable($model->getTable());
    }

    private function routeUrl(string $routeName, ?string $fallbackRouteName = null): string
    {
        if (Route::has($routeName)) {
            return route($routeName);
        }

        if ($fallbackRouteName && Route::has($fallbackRouteName)) {
            return route($fallbackRouteName);
        }

        return url('/');
    }
}
