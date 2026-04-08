<?php

namespace Platform\ExperienceCms\Models;

use Illuminate\Database\Eloquent\Model;

class ContentEntry extends Model
{
    protected $fillable = [
        'type',
        'title',
        'slug',
        'body_json',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'body_json' => 'array',
        ];
    }
}
