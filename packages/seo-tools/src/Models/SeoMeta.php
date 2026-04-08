<?php

namespace Platform\SeoTools\Models;

use Illuminate\Database\Eloquent\Model;

class SeoMeta extends Model
{
    protected $table = 'seo_meta';

    protected $fillable = [
        'title',
        'description',
        'keywords',
        'robots',
        'og_json',
        'canonical_url',
    ];

    protected function casts(): array
    {
        return [
            'og_json' => 'array',
        ];
    }
}
