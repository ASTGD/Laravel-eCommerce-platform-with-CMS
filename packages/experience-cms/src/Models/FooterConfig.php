<?php

namespace Platform\ExperienceCms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FooterConfig extends Model
{
    protected $fillable = [
        'code',
        'settings_json',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'settings_json' => 'array',
            'is_default'    => 'boolean',
        ];
    }

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }
}
