<?php

namespace Platform\CommerceCore\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliateSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'array',
    ];
}
