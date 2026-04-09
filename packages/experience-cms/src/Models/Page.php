<?php

namespace Platform\ExperienceCms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Platform\SeoTools\Models\SeoMeta;
use Platform\ThemeCore\Models\ThemePreset;

class Page extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    protected $fillable = [
        'title',
        'slug',
        'type',
        'template_id',
        'header_config_id',
        'footer_config_id',
        'menu_id',
        'theme_preset_id',
        'settings_json',
        'status',
        'seo_meta_id',
        'published_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'settings_json' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function seoMeta(): BelongsTo
    {
        return $this->belongsTo(SeoMeta::class, 'seo_meta_id');
    }

    public function headerConfig(): BelongsTo
    {
        return $this->belongsTo(HeaderConfig::class);
    }

    public function footerConfig(): BelongsTo
    {
        return $this->belongsTo(FooterConfig::class);
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function themePreset(): BelongsTo
    {
        return $this->belongsTo(ThemePreset::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(PageSection::class)->orderBy('sort_order');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(PageVersion::class)->orderByDesc('version_number');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(PageAssignment::class)->orderByDesc('priority');
    }

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }
}
