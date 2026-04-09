<?php

namespace Platform\ExperienceCms\Contracts;

use Illuminate\Support\Collection;

interface ContentEntryResolverContract
{
    public function resolve(array $payload = [], array $context = []): Collection;
}
