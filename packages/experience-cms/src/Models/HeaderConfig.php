<?php

declare(strict_types=1);

namespace ExperienceCms\Models;

use Illuminate\Database\Eloquent\Model;

class HeaderConfig extends Model
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
            'is_default' => 'bool',
        ];
    }
}
