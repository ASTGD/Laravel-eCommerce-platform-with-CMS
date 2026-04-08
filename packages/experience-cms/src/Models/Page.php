<?php

declare(strict_types=1);

namespace ExperienceCms\Models;

use App\Models\User;
use ExperienceCms\Enums\PageStatus;
use ExperienceCms\Enums\PageType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use SeoTools\Models\SeoMeta;

class Page extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'type',
        'template_id',
        'status',
        'seo_meta_id',
        'published_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => PageType::class,
            'status' => PageStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PageStatus::Published->value);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function seoMeta(): BelongsTo
    {
        return $this->belongsTo(SeoMeta::class, 'seo_meta_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(PageSection::class)->orderBy('sort_order');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(PageVersion::class)->orderByDesc('version_number');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
