<?php

namespace Platform\ExperienceCms\Services;

use Platform\ExperienceCms\Contracts\FooterResolverContract;
use Platform\ExperienceCms\Models\FooterConfig;

class FooterResolver implements FooterResolverContract
{
    public function resolve(?string $code = null): ?FooterConfig
    {
        $query = FooterConfig::query();

        if ($code) {
            return $query->where('code', $code)->first();
        }

        return $query->orderByDesc('is_default')->orderBy('code')->first();
    }
}
