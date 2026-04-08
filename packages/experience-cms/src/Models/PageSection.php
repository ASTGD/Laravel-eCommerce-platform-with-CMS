<?php

namespace Platform\ExperienceCms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PageSection extends Model
{
    protected $fillable = [
        'page_id',
        'template_area_id',
        'section_type_id',
        'sort_order',
        'title',
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

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function sectionType(): BelongsTo
    {
        return $this->belongsTo(SectionType::class);
    }

    public function components(): HasMany
    {
        return $this->hasMany(SectionComponent::class)->orderBy('sort_order');
    }
}
