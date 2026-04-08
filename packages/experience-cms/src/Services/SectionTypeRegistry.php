<?php

namespace Platform\ExperienceCms\Services;

use Illuminate\Support\Collection;
use Platform\ExperienceCms\Contracts\SectionTypeContract;

class SectionTypeRegistry
{
    /**
     * @param  array<int, SectionTypeContract>  $definitions
     */
    public function __construct(protected array $definitions) {}

    public function all(): Collection
    {
        return collect($this->definitions);
    }

    public function find(string $code): ?SectionTypeContract
    {
        return $this->all()->first(fn (SectionTypeContract $definition) => $definition->code() === $code);
    }
}
