<?php

declare(strict_types=1);

namespace ExperienceCms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Template extends Model
{
    protected $fillable = [
        'name',
        'code',
        'page_type',
        'schema_json',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'schema_json' => 'array',
            'is_active' => 'bool',
        ];
    }

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }
}
