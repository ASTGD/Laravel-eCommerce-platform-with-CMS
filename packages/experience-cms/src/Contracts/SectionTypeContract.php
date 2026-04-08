<?php

declare(strict_types=1);

namespace ExperienceCms\Contracts;

interface SectionTypeContract
{
    public function code(): string;

    public function name(): string;

    public function category(): string;

    public function configSchema(): array;

    public function defaultSettings(): array;

    public function validationRules(): array;

    public function supportedDataSources(): array;

    public function previewData(): array;

    public function view(): string;

    public function supportsComponents(): bool;
}
