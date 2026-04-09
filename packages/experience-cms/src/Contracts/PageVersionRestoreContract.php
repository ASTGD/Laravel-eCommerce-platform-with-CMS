<?php

namespace Platform\ExperienceCms\Contracts;

use Platform\ExperienceCms\Models\Page;
use Platform\ExperienceCms\Models\PageVersion;

interface PageVersionRestoreContract
{
    public function restore(Page $page, PageVersion $version, ?string $note = null): Page;
}
