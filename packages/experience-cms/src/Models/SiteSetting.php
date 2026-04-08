<?php

namespace Platform\ExperienceCms\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = [
        'key',
        'value_json',
        'group',
    ];

    protected function casts(): array
    {
        return [
            'value_json' => 'array',
        ];
    }
}
