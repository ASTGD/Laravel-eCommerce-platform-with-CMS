<?php

declare(strict_types=1);

namespace ExperienceCms\Contracts;

interface ComponentTypeContract
{
    public function code(): string;

    public function name(): string;

    public function configSchema(): array;

    public function defaultSettings(): array;

    public function validationRules(): array;

    public function view(): string;
}
