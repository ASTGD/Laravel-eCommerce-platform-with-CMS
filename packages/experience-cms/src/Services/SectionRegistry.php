<?php

declare(strict_types=1);

namespace ExperienceCms\Services;

use ExperienceCms\Contracts\SectionTypeContract;
use Illuminate\Support\Collection;

class SectionRegistry
{
    /**
     * @param  array<int, SectionTypeContract>  $definitions
     */
    public function __construct(
        private readonly array $definitions,
    ) {}

    /**
     * @return Collection<int, SectionTypeContract>
     */
    public function all(): Collection
    {
        return collect($this->definitions);
    }

    public function find(string $code): ?SectionTypeContract
    {
        return $this->all()->first(fn (SectionTypeContract $definition): bool => $definition->code() === $code);
    }
}
