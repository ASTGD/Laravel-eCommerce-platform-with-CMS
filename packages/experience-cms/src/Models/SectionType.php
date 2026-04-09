<?php

namespace Platform\ExperienceCms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SectionType extends Model
{
    protected $fillable = [
        'name',
        'code',
        'category',
        'config_schema_json',
        'supports_components',
        'allowed_data_sources_json',
        'renderer_class',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'config_schema_json'        => 'array',
            'supports_components'       => 'boolean',
            'allowed_data_sources_json' => 'array',
            'is_active'                 => 'boolean',
        ];
    }

    public function pageSections(): HasMany
    {
        return $this->hasMany(PageSection::class);
    }
}
