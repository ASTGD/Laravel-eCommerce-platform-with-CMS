<?php

namespace Platform\ExperienceCms\Services;

use Illuminate\Support\Collection;
use Platform\ExperienceCms\Contracts\ComponentTypeContract;

class ComponentTypeRegistry
{
    /**
     * @param  array<int, ComponentTypeContract>  $definitions
     */
    public function __construct(protected array $definitions) {}

    public function all(): Collection
    {
        return collect($this->definitions);
    }
}
