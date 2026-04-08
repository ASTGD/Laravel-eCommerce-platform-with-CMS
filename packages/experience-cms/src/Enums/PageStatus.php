<?php

declare(strict_types=1);

namespace ExperienceCms\Enums;

enum PageStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Unpublished = 'unpublished';
}
