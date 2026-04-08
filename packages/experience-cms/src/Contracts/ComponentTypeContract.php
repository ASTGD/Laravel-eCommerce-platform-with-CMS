<?php

namespace Platform\ExperienceCms\Contracts;

interface ComponentTypeContract
{
    public function code(): string;

    public function label(): string;

    public function configSchema(): array;

    public function defaultConfig(): array;

    public function validationRules(): array;

    public function rendererView(): string;

    public function previewView(): ?string;

    public function toArray(): array;
}
