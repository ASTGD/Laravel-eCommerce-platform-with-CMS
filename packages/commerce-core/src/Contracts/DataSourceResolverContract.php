<?php

namespace Platform\CommerceCore\Contracts;

use Illuminate\Support\Collection;

interface DataSourceResolverContract
{
    public function resolve(string $sourceType, array $payload = [], array $context = []): Collection;
}
