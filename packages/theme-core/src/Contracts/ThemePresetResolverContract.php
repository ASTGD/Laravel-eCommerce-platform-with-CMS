<?php

namespace Platform\ThemeCore\Contracts;

use Platform\ThemeCore\Models\ThemePreset;

interface ThemePresetResolverContract
{
    public function resolve(?string $code = null): ?ThemePreset;
}
