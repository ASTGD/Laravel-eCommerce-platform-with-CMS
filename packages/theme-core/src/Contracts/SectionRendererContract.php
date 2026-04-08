<?php

declare(strict_types=1);

namespace ThemeCore\Contracts;

use ExperienceCms\Models\PageSection;
use Illuminate\Contracts\View\View;

interface SectionRendererContract
{
    public function make(PageSection $section, array $context = []): View;

    public function payload(PageSection $section, array $context = []): array;
}
