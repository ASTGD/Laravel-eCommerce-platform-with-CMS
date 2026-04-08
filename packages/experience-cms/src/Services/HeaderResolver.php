<?php

namespace Platform\ExperienceCms\Services;

use Platform\ExperienceCms\Contracts\HeaderResolverContract;
use Platform\ExperienceCms\Models\HeaderConfig;

class HeaderResolver implements HeaderResolverContract
{
    public function resolve(?string $code = null): ?HeaderConfig
    {
        $query = HeaderConfig::query();

        if ($code) {
            return $query->where('code', $code)->first();
        }

        return $query->orderByDesc('is_default')->orderBy('code')->first();
    }
}
