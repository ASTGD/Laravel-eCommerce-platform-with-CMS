<?php

namespace Platform\ExperienceCms\Contracts;

use Platform\ExperienceCms\Models\Page;

interface PagePreviewServiceContract
{
    public function build(Page $page, array $context = []): array;
}
