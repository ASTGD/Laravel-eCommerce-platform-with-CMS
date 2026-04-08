<?php

namespace Platform\ExperienceCms\Contracts;

use Platform\ExperienceCms\Models\HeaderConfig;

interface HeaderResolverContract
{
    public function resolve(?string $code = null): ?HeaderConfig;
}
