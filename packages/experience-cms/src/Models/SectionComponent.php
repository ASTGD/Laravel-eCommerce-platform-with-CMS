<?php

namespace Platform\ExperienceCms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SectionComponent extends Model
{
    protected $fillable = [
        'page_section_id',
        'component_type_id',
        'sort_order',
        'settings_json',
        'visibility_rules_json',
        'data_source_type',
        'data_source_payload_json',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'settings_json'            => 'array',
            'visibility_rules_json'    => 'array',
            'data_source_payload_json' => 'array',
            'is_active'                => 'boolean',
        ];
    }

    public function pageSection(): BelongsTo
    {
        return $this->belongsTo(PageSection::class);
    }

    public function componentType(): BelongsTo
    {
        return $this->belongsTo(ComponentType::class);
    }
}
