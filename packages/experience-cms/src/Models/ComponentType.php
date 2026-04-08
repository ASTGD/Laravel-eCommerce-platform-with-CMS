<?php

namespace Platform\ExperienceCms\Models;

use Illuminate\Database\Eloquent\Model;

class ComponentType extends Model
{
    protected $fillable = [
        'name',
        'code',
        'config_schema_json',
        'renderer_class',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'config_schema_json' => 'array',
            'is_active'          => 'boolean',
        ];
    }
}
