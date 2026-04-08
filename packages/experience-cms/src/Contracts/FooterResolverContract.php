<?php

namespace Platform\ExperienceCms\Contracts;

use Platform\ExperienceCms\Models\FooterConfig;

interface FooterResolverContract
{
    public function resolve(?string $code = null): ?FooterConfig;
}
