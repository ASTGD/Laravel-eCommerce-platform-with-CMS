<?php

namespace Platform\ThemeCore\Models;

use Illuminate\Database\Eloquent\Model;

class ThemePreset extends Model
{
    protected $table = 'theme_presets';

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
            'tokens_json'   => 'array',
            'settings_json' => 'array',
            'is_default'    => 'boolean',
            'is_active'     => 'boolean',
        ];
    }
}
