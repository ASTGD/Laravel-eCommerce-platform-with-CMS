<?php

namespace Platform\ExperienceCms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageVersion extends Model
{
    protected $fillable = [
        'page_id',
        'version_number',
        'snapshot_json',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_json' => 'array',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
