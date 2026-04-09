<?php

namespace Platform\ExperienceCms\ComponentTypes;

use Platform\ExperienceCms\Contracts\ComponentTypeContract;

abstract class AbstractComponentType implements ComponentTypeContract
{
    public function previewView(): ?string
    {
        return $this->rendererView();
    }

    public function toArray(): array
    {
        return [
            'name'               => $this->label(),
            'code'               => $this->code(),
            'config_schema_json' => $this->configSchema(),
            'renderer_class'     => static::class,
            'is_active'          => true,
        ];
    }
}
