<?php

namespace Platform\ExperienceCms\Contracts;

interface SiteSettingsResolverContract
{
    public function all(): array;

    public function group(string $group): array;

    public function value(string $key, array $default = []): array;
}
