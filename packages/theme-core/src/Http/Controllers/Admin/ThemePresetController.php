<?php

namespace Platform\ThemeCore\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Platform\ThemeCore\Http\Requests\Admin\ThemePresetRequest;
use Platform\ThemeCore\Models\ThemePreset;
use Webkul\Core\Models\Channel;

class ThemePresetController extends Controller
{
    public function index(): View
    {
        $themePresetColumns = [
            'name' => $this->hasThemePresetColumn('name'),
            'code' => $this->hasThemePresetColumn('code'),
            'description' => $this->hasThemePresetColumn('description'),
            'is_active' => $this->hasThemePresetColumn('is_active'),
            'is_default' => $this->hasThemePresetColumn('is_default'),
            'active' => $this->hasThemePresetColumn('active'),
            'default' => $this->hasThemePresetColumn('default'),
            'status' => $this->hasThemePresetColumn('status'),
        ];

        $presets = $this->themePresetTableExists()
            ? ThemePreset::query()
                ->when($themePresetColumns['is_default'], fn ($query) => $query->orderByDesc('is_default'))
                ->when($themePresetColumns['is_active'], fn ($query) => $query->orderByDesc('is_active'))
                ->when($themePresetColumns['name'], fn ($query) => $query->orderBy('name'))
                ->get()
            : collect();

        $activePreset = $this->resolveActivePreset($presets, $themePresetColumns);
        $themeActions = [
            'create' => $this->routeUrl('admin.theme.presets.create'),
            'index' => $this->routeUrl('admin.theme.presets.index'),
            'preview' => $this->routeUrl('shop.home.index') ?? url('/'),
            'edit_route_exists' => Route::has('admin.theme.presets.edit'),
            'delete_route_exists' => Route::has('admin.theme.presets.destroy'),
            'activate_route' => collect([
                'admin.theme.presets.activate',
                'admin.theme.presets.set-active',
            ])->first(fn ($route) => Route::has($route)),
        ];

        return view('theme-core::admin.theme-presets.index', [
            'presets' => $presets,
            'activePreset' => $activePreset,
            'activePresetId' => $activePreset?->getKey(),
            'themeActions' => $themeActions,
            'themePresetColumns' => $themePresetColumns,
        ]);
    }

    public function create(): View
    {
        return view('theme-core::admin.theme-presets.form', [
            'preset' => new ThemePreset([
                'is_active' => true,
                'is_default' => false,
                'tokens_json' => [],
                'settings_json' => [],
            ]),
        ]);
    }

    public function store(ThemePresetRequest $request): RedirectResponse
    {
        $preset = ThemePreset::query()->create($request->payload());

        $this->syncDefaultPreset($preset);

        return redirect()
            ->route('admin.theme.presets.edit', $preset)
            ->with('success', 'Theme preset created.');
    }

    public function edit(ThemePreset $platformThemePreset): View
    {
        return view('theme-core::admin.theme-presets.form', [
            'preset' => $platformThemePreset,
        ]);
    }

    public function update(ThemePresetRequest $request, ThemePreset $platformThemePreset): RedirectResponse
    {
        $platformThemePreset->update($request->payload());

        $this->syncDefaultPreset($platformThemePreset);

        return redirect()
            ->route('admin.theme.presets.edit', $platformThemePreset)
            ->with('success', 'Theme preset updated.');
    }

    public function destroy(ThemePreset $platformThemePreset): RedirectResponse
    {
        $platformThemePreset->delete();

        return redirect()
            ->route('admin.theme.presets.index')
            ->with('success', 'Theme preset deleted.');
    }

    public function setActive(int|string $id): RedirectResponse
    {
        $themePreset = ThemePreset::query()->find($id);

        if (! $themePreset) {
            return redirect()
                ->back()
                ->with('error', 'Theme preset not found.');
        }

        $activationColumns = $this->themeActivationColumns();

        if (empty($activationColumns)) {
            return redirect()
                ->back()
                ->with('error', 'No theme activation column is available.');
        }

        $shopThemeCode = $this->shopThemeCode($themePreset);

        if (! $this->isRegisteredShopTheme($shopThemeCode)) {
            return redirect()
                ->back()
                ->with('error', sprintf('Shop theme [%s] is not registered.', $shopThemeCode));
        }

        DB::transaction(function () use ($themePreset, $activationColumns, $shopThemeCode): void {
            $inactiveValues = [];
            $activeValues = [];

            foreach ($activationColumns as $column) {
                if ($column === 'status') {
                    $inactiveValues[$column] = 'inactive';
                    $activeValues[$column] = 'active';

                    continue;
                }

                $inactiveValues[$column] = false;
                $activeValues[$column] = true;
            }

            ThemePreset::query()
                ->whereKeyNot($themePreset->getKey())
                ->update($inactiveValues);

            ThemePreset::query()
                ->whereKey($themePreset->getKey())
                ->update($activeValues);

            $channel = core()->getCurrentChannel();

            if ($channel) {
                Channel::query()
                    ->whereKey($channel->getKey())
                    ->update(['theme' => $shopThemeCode]);

                $channel->theme = $shopThemeCode;
                core()->setCurrentChannel($channel);
            }
        });

        return redirect()
            ->back()
            ->with('success', 'Theme preset activated successfully.');
    }

    protected function syncDefaultPreset(ThemePreset $themePreset): void
    {
        if (! $themePreset->is_default) {
            return;
        }

        ThemePreset::query()
            ->whereKeyNot($themePreset->getKey())
            ->update(['is_default' => false]);
    }

    protected function resolveActivePreset($presets, array $themePresetColumns): ?ThemePreset
    {
        if ($presets->isEmpty()) {
            return null;
        }

        if ($themePresetColumns['is_active'] && $themePresetColumns['is_default']) {
            return $presets->first(fn ($preset) => $preset->is_active && $preset->is_default)
                ?? $presets->firstWhere('is_active', true);
        }

        if ($themePresetColumns['is_default']) {
            return $presets->firstWhere('is_default', true);
        }

        if ($themePresetColumns['is_active']) {
            return $presets->firstWhere('is_active', true);
        }

        if ($themePresetColumns['default']) {
            return $presets->firstWhere('default', true);
        }

        if ($themePresetColumns['active']) {
            return $presets->firstWhere('active', true);
        }

        if ($themePresetColumns['status']) {
            return $presets->firstWhere('status', 'active');
        }

        return null;
    }

    protected function themeActivationColumns(): array
    {
        $activeColumns = array_values(array_filter([
            $this->hasThemePresetColumn('is_active') ? 'is_active' : null,
            $this->hasThemePresetColumn('active') ? 'active' : null,
            $this->hasThemePresetColumn('status') ? 'status' : null,
        ]));

        if (! empty($activeColumns)) {
            return $activeColumns;
        }

        return array_values(array_filter([
            $this->hasThemePresetColumn('is_default') ? 'is_default' : null,
            $this->hasThemePresetColumn('default') ? 'default' : null,
        ]));
    }

    protected function shopThemeCode(ThemePreset $themePreset): string
    {
        $settingsThemeCode = data_get($themePreset->settings_json, 'shop_theme_code');

        if (is_string($settingsThemeCode) && $settingsThemeCode !== '') {
            return $settingsThemeCode;
        }

        if ($this->isRegisteredShopTheme((string) $themePreset->code)) {
            return (string) $themePreset->code;
        }

        return (string) config('themes.shop-default', 'default');
    }

    protected function isRegisteredShopTheme(string $themeCode): bool
    {
        return array_key_exists($themeCode, config('themes.shop', []));
    }

    protected function themePresetTableExists(): bool
    {
        return Schema::hasTable((new ThemePreset)->getTable());
    }

    protected function hasThemePresetColumn(string $column): bool
    {
        return $this->themePresetTableExists()
            && Schema::hasColumn((new ThemePreset)->getTable(), $column);
    }

    protected function routeUrl(string $routeName): ?string
    {
        if (! Route::has($routeName)) {
            return null;
        }

        return route($routeName);
    }
}
