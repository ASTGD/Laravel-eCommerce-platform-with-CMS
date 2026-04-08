<?php

declare(strict_types=1);

namespace SeoTools\Models;

use Illuminate\Database\Eloquent\Model;

class SeoMeta extends Model
{
    protected $table = 'seo_meta';

    protected $fillable = [
        'title',
        'description',
        'canonical_url',
        'open_graph_title',
        'open_graph_description',
        'open_graph_image_url',
        'robots',
    ];
}
