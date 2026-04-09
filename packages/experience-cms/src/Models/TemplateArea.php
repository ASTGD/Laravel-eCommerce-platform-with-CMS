<?php

namespace Platform\ExperienceCms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TemplateArea extends Model
{
    protected $fillable = [
        'template_id',
        'code',
        'name',
        'rules_json',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'rules_json' => 'array',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function pageSections(): HasMany
    {
        return $this->hasMany(PageSection::class);
    }
}
