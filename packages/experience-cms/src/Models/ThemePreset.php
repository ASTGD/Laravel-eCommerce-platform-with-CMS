<?php

declare(strict_types=1);

namespace ExperienceCms\Models;

use Illuminate\Database\Eloquent\Model;

class ThemePreset extends Model
{
    protected $fillable = [
        'name',
        'code',
        'tokens_json',
        'settings_json',
        'is_default',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'tokens_json' => 'array',
            'settings_json' => 'array',
            'is_default' => 'bool',
            'is_active' => 'bool',
        ];
    }
}
