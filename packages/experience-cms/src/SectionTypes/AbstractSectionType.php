<?php

declare(strict_types=1);

namespace ExperienceCms\SectionTypes;

use ExperienceCms\Contracts\SectionTypeContract;

abstract class AbstractSectionType implements SectionTypeContract
{
    public function supportsComponents(): bool
    {
        return false;
    }

    public function previewData(): array
    {
        return [];
    }
}
