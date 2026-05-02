<?php

namespace Platform\ThemeCore\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Platform\ThemeCore\Http\Requests\Admin\ThemePresetRequest;
use Platform\ThemeCore\Models\ThemePreset;
use Platform\ThemeCore\Models\ThemeTokenSet;

class ThemePresetController extends Controller
{
    public function index(): View
    {
        $presets = ThemePreset::query()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        $defaultPreset = $presets->firstWhere('is_default', true) ?? $presets->first();

        return view('theme-core::admin.theme-presets.index', [
            'presets'          => $presets,
            'defaultPreset'     => $defaultPreset,
            'activePresetCount' => $presets->where('is_active', true)->count(),
            'tokenSetCount'     => ThemeTokenSet::query()->count(),
            'variantCount'      => $presets
                ->pluck('settings_json')
                ->map(fn ($settings) => data_get($settings, 'product_card_variant'))
                ->filter()
                ->unique()
                ->count(),
        ]);
    }

    public function create(): View
    {
        return view('theme-core::admin.theme-presets.form', [
            'preset' => new ThemePreset([
                'is_active'  => true,
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

    protected function syncDefaultPreset(ThemePreset $themePreset): void
    {
        if (! $themePreset->is_default) {
            return;
        }

        ThemePreset::query()
            ->whereKeyNot($themePreset->getKey())
            ->update(['is_default' => false]);
    }
}
