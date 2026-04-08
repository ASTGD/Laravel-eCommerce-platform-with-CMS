<?php

namespace Platform\ExperienceCms\Contracts;

use Platform\ExperienceCms\Models\Menu;

interface MenuResolverContract
{
    public function resolve(?string $code = null): ?Menu;
}
