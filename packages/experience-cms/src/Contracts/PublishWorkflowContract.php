<?php

namespace Platform\ExperienceCms\Contracts;

use Platform\ExperienceCms\Models\Page;

interface PublishWorkflowContract
{
    public function publish(Page $page, ?string $note = null): Page;
}
