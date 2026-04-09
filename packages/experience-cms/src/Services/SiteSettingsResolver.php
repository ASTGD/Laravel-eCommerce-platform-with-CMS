<?php

namespace Platform\ExperienceCms\Services;

use Platform\ExperienceCms\Contracts\SiteSettingsResolverContract;
use Platform\ExperienceCms\Models\SiteSetting;

class SiteSettingsResolver implements SiteSettingsResolverContract
{
    public function all(): array
    {
        return SiteSetting::query()
            ->orderBy('group')
            ->orderBy('key')
            ->get()
            ->mapWithKeys(fn (SiteSetting $setting) => [$setting->key => $setting->value_json ?? []])
            ->all();
    }

    public function group(string $group): array
    {
        return SiteSetting::query()
            ->where('group', $group)
            ->orderBy('key')
            ->get()
            ->mapWithKeys(fn (SiteSetting $setting) => [$setting->key => $setting->value_json ?? []])
            ->all();
    }

    public function value(string $key, array $default = []): array
    {
        return SiteSetting::query()
            ->where('key', $key)
            ->value('value_json') ?? $default;
    }
}
