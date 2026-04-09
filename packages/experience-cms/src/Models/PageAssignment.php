<?php

namespace Platform\ExperienceCms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageAssignment extends Model
{
    public const SCOPE_GLOBAL = 'global';

    public const SCOPE_ENTITY = 'entity';

    public const ENTITY_CATEGORY = 'category';

    public const ENTITY_PRODUCT = 'product';

    protected $fillable = [
        'page_id',
        'page_type',
        'scope_type',
        'entity_type',
        'entity_id',
        'priority',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'priority'  => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
