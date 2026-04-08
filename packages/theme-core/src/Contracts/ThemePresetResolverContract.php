<?php

declare(strict_types=1);

namespace ThemeCore\Contracts;

use ExperienceCms\Models\ThemePreset;

interface ThemePresetResolverContract
{
    public function resolve(?string $code = null): ThemePreset;

    public function tokenMap(?string $code = null): array;
}
