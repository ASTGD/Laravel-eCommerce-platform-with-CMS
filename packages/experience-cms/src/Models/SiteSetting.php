<?php

declare(strict_types=1);

namespace ExperienceCms\Models;

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

    public static function valueFor(string $key, mixed $default = null): mixed
    {
        $setting = static::query()->where('key', $key)->first();

        return $setting?->value_json ?? $default;
    }
}
