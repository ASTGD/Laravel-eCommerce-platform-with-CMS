<?php

declare(strict_types=1);

namespace ExperienceCms\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use ExperienceCms\Http\Requests\Admin\ThemePresetRequest;
use ExperienceCms\Models\ThemePreset;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ThemePresetController extends Controller
{
    public function index(): View
    {
        return view('experience-cms::admin.theme-presets.index', [
            'themePresets' => ThemePreset::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('experience-cms::admin.theme-presets.form', ['themePreset' => new ThemePreset(['is_active' => true]), 'mode' => 'create']);
    }

    public function store(ThemePresetRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $this->syncDefaultFlag((bool) $data['is_default']);

        $themePreset = ThemePreset::query()->create($data);

        return redirect()->route('admin.theme-presets.edit', $themePreset)->with('status', 'Theme preset created.');
    }

    public function edit(string $theme_preset): View
    {
        $themePreset = ThemePreset::query()->findOrFail($theme_preset);

        return view('experience-cms::admin.theme-presets.form', ['themePreset' => $themePreset, 'mode' => 'edit']);
    }

    public function update(ThemePresetRequest $request, string $theme_preset): RedirectResponse
    {
        $themePreset = ThemePreset::query()->findOrFail($theme_preset);
        $data = $request->validated();
        $this->syncDefaultFlag((bool) $data['is_default'], $themePreset->id);
        $themePreset->update($data);

        return redirect()->route('admin.theme-presets.edit', $themePreset)->with('status', 'Theme preset updated.');
    }

    public function destroy(string $theme_preset): RedirectResponse
    {
        $themePreset = ThemePreset::query()->findOrFail($theme_preset);
        $themePreset->delete();

        return redirect()->route('admin.theme-presets.index')->with('status', 'Theme preset deleted.');
    }

    private function syncDefaultFlag(bool $isDefault, ?int $currentId = null): void
    {
        if (! $isDefault) {
            return;
        }

        ThemePreset::query()
            ->when($currentId !== null, fn ($query) => $query->whereKeyNot($currentId))
            ->update(['is_default' => false]);
    }
}
