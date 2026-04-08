<?php

namespace Platform\ExperienceCms\SectionTypes;

use Platform\ExperienceCms\Contracts\SectionTypeContract;

abstract class AbstractSectionType implements SectionTypeContract
{
    public function supportsComponents(): bool
    {
        return false;
    }

    public function previewView(): ?string
    {
        return $this->rendererView();
    }

    public function toArray(): array
    {
        return [
            'name'                      => $this->label(),
            'code'                      => $this->code(),
            'category'                  => $this->category(),
            'config_schema_json'        => $this->configSchema(),
            'supports_components'       => $this->supportsComponents(),
            'allowed_data_sources_json' => $this->allowedDataSources(),
            'renderer_class'            => static::class,
            'is_active'                 => true,
        ];
    }
}
