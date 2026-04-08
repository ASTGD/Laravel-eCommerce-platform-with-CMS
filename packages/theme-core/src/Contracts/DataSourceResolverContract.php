<?php

declare(strict_types=1);

namespace ThemeCore\Contracts;

use ExperienceCms\Models\PageSection;

interface DataSourceResolverContract
{
    public function resolve(PageSection $section, array $context = []): array;
}
