<?php

namespace Platform\ThemeCore\Models;

use Illuminate\Database\Eloquent\Model;

class ThemeTokenSet extends Model
{
    protected $table = 'theme_token_sets';

    protected $fillable = [
        'name',
        'code',
        'tokens_json',
    ];

    protected function casts(): array
    {
        return [
            'tokens_json' => 'array',
        ];
    }
}
