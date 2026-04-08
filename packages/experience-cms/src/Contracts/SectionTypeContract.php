<?php

namespace Platform\ExperienceCms\Contracts;

interface SectionTypeContract
{
    public function code(): string;

    public function label(): string;

    public function category(): string;

    public function configSchema(): array;

    public function defaultConfig(): array;

    public function validationRules(): array;

    public function allowedDataSources(): array;

    public function supportsComponents(): bool;

    public function rendererView(): string;

    public function previewView(): ?string;

    public function toArray(): array;
}
