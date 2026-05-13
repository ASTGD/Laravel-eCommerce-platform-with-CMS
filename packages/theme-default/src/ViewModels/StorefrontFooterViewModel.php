<?php

namespace Platform\ThemeDefault\ViewModels;

use Illuminate\Support\Str;
use Platform\ExperienceCms\Contracts\FooterResolverContract;
use Platform\ExperienceCms\Contracts\SiteSettingsResolverContract;
use Platform\ExperienceCms\Models\Menu;
use Platform\ExperienceCms\Models\MenuItem;
use Throwable;

class StorefrontFooterViewModel
{
    public function __construct(
        protected FooterResolverContract $footers,
        protected SiteSettingsResolverContract $siteSettings,
    ) {}

    public function build(array $fallback = []): array
    {
        $settings = $this->footerSettings();
        $identity = $this->siteIdentity();
        $contactSettings = $this->siteContact();
        $channel = core()->getCurrentChannel();

        return [
            'brandName' => $this->stringValue(
                $settings['name']
                    ?? $settings['headline']
                    ?? $identity['brand_name']
                    ?? $identity['store_name']
                    ?? $channel?->name
                    ?? config('app.name')
            ),
            'logoUrl' => $this->publicUrl(
                $settings['logo_url']
                    ?? $identity['logo_url']
                    ?? $channel?->logo_url
                    ?? asset('images/astgd-ecommerce-logo.webp')
            ),
            'description' => $this->stringValue(
                $settings['description']
                    ?? $contactSettings['description']
                    ?? $fallback['description']
                    ?? ''
            ),
            'newsletter' => [
                'enabled' => (bool) data_get($settings, 'newsletter.enabled', false),
                'heading' => $this->stringValue(data_get($settings, 'newsletter.heading', 'Join our newsletter')),
                'text' => $this->stringValue(data_get($settings, 'newsletter.text', 'Get store updates and offers.')),
            ],
            'contact' => [
                'email' => $this->stringValue(data_get($settings, 'contact.email', $contactSettings['email'] ?? '')),
                'phone' => $this->stringValue(data_get($settings, 'contact.phone', $contactSettings['phone'] ?? '')),
            ],
            'socialLinks' => $this->socialLinks($settings, $fallback['socialLinks'] ?? []),
            'columns' => $this->footerColumns($settings, $fallback['columns'] ?? []),
            'copyrightText' => $this->stringValue(
                $settings['copyright_text']
                    ?? $fallback['copyrightText']
                    ?? 'Copyright '.now()->year.' Storefront. All rights reserved.'
            ),
            'variant' => $this->stringValue($settings['variant'] ?? 'simple'),
            'homeUrl' => route('shop.home.index'),
        ];
    }

    protected function footerSettings(): array
    {
        try {
            $settings = $this->footers->resolve()?->settings_json;

            return is_array($settings) ? $settings : [];
        } catch (Throwable) {
            return [];
        }
    }

    protected function siteIdentity(): array
    {
        try {
            return $this->siteSettings->value('store.identity', []);
        } catch (Throwable) {
            return [];
        }
    }

    protected function siteContact(): array
    {
        try {
            return $this->siteSettings->value('store.contact', []);
        } catch (Throwable) {
            return [];
        }
    }

    protected function socialLinks(array $settings, array $fallbackLinks): array
    {
        $labels = [
            'facebook' => ['label' => 'Facebook', 'short_label' => 'FB'],
            'instagram' => ['label' => 'Instagram', 'short_label' => 'IG'],
            'x' => ['label' => 'X', 'short_label' => 'X'],
            'youtube' => ['label' => 'YouTube', 'short_label' => 'YT'],
            'tiktok' => ['label' => 'TikTok', 'short_label' => 'TT'],
        ];

        $links = collect($labels)
            ->map(function (array $meta, string $key) use ($settings): ?array {
                $url = $this->stringValue(data_get($settings, 'social.'.$key));

                if ($url === '') {
                    return null;
                }

                return [
                    'label' => $meta['label'],
                    'short_label' => $meta['short_label'],
                    'icon' => $key,
                    'url' => $url,
                ];
            })
            ->filter()
            ->values()
            ->all();

        if ($links !== []) {
            return $links;
        }

        return collect($fallbackLinks)
            ->map(fn (mixed $link): array => [
                'label' => $this->stringValue(data_get($link, 'label', 'Social')),
                'short_label' => $this->stringValue(data_get($link, 'short_label', data_get($link, 'label', ''))),
                'icon' => $this->socialIconKey($this->stringValue(data_get($link, 'icon', data_get($link, 'label', '')))),
                'url' => $this->stringValue(data_get($link, 'url', '#')) ?: '#',
            ])
            ->filter(fn (array $link): bool => filled($link['label']))
            ->values()
            ->all();
    }

    protected function socialIconKey(string $value): string
    {
        $key = Str::of($value)->lower()->replace([' ', '_'], '-')->toString();

        return match ($key) {
            'facebook', 'fb' => 'facebook',
            'instagram', 'ig' => 'instagram',
            'x', 'twitter' => 'x',
            'youtube', 'yt' => 'youtube',
            'tiktok', 'tik-tok', 'tt' => 'tiktok',
            default => 'link',
        };
    }

    protected function footerColumns(array $settings, array $fallbackColumns): array
    {
        $configuredColumns = $this->configuredFooterColumns($settings);

        if ($configuredColumns !== []) {
            return $configuredColumns;
        }

        $selectedMenuId = (int) data_get($settings, 'navigation.menu_id');
        $menuColumn = $selectedMenuId > 0 ? $this->footerMenuColumn($selectedMenuId) : null;

        if ($menuColumn !== null) {
            return [$menuColumn];
        }

        $footerMenuColumns = $this->footerMenuColumns();

        if ($footerMenuColumns !== []) {
            return $footerMenuColumns;
        }

        return collect($fallbackColumns)
            ->map(fn (mixed $column): array => [
                'title' => $this->stringValue(data_get($column, 'title', 'Links')),
                'links' => collect(data_get($column, 'links', []))
                    ->map(fn (mixed $link): array => [
                        'label' => $this->stringValue(data_get($link, 'label', 'Link')),
                        'url' => $this->stringValue(data_get($link, 'url', '#')) ?: '#',
                        'open_in_new_tab' => (bool) data_get($link, 'open_in_new_tab', false),
                    ])
                    ->filter(fn (array $link): bool => filled($link['label']))
                    ->values()
                    ->all(),
            ])
            ->filter(fn (array $column): bool => filled($column['title']) && $column['links'] !== [])
            ->values()
            ->all();
    }

    protected function configuredFooterColumns(array $settings): array
    {
        $columns = collect(data_get($settings, 'navigation.columns', []))
            ->filter(fn (mixed $column): bool => (bool) data_get($column, 'enabled', true))
            ->sortBy(fn (mixed $column): int => (int) data_get($column, 'sort_order', 0))
            ->take(4)
            ->values();

        if ($columns->isEmpty()) {
            return [];
        }

        return $columns
            ->map(function (mixed $column): ?array {
                $menuId = (int) data_get($column, 'menu_id');

                if ($menuId <= 0) {
                    return null;
                }

                return $this->footerMenuColumn(
                    $menuId,
                    $this->stringValue(data_get($column, 'title'))
                );
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function footerMenuColumns(): array
    {
        try {
            $menus = Menu::query()
                ->where('location', 'footer')
                ->where('is_active', true)
                ->with(['items' => fn ($query) => $query
                    ->whereNull('parent_id')
                    ->where('is_active', true)
                    ->orderBy('sort_order')])
                ->orderBy('name')
                ->get();
        } catch (Throwable) {
            return [];
        }

        return $menus
            ->map(fn (Menu $menu): ?array => $this->menuToColumn($menu))
            ->filter()
            ->values()
            ->all();
    }

    protected function footerMenuColumn(?int $menuId = null, ?string $title = null): ?array
    {
        try {
            $menu = Menu::query()
                ->when(
                    $menuId,
                    fn ($query) => $query->whereKey($menuId),
                    fn ($query) => $query->where('location', 'footer')
                )
                ->where('is_active', true)
                ->with(['items' => fn ($query) => $query
                    ->whereNull('parent_id')
                    ->where('is_active', true)
                    ->orderBy('sort_order')])
                ->orderBy('id')
                ->first();
        } catch (Throwable) {
            return null;
        }

        if (! $menu || $menu->items->isEmpty()) {
            return null;
        }

        return $this->menuToColumn($menu, $title);
    }

    protected function menuToColumn(Menu $menu, ?string $title = null): ?array
    {
        if ($menu->items->isEmpty()) {
            return null;
        }

        return [
            'title' => filled($title) ? $title : $menu->name,
            'links' => $menu->items
                ->map(fn (MenuItem $item): array => [
                    'label' => $item->title,
                    'url' => $item->target ?: '#',
                    'open_in_new_tab' => (bool) data_get($item->settings_json, 'open_in_new_tab', false),
                ])
                ->values()
                ->all(),
        ];
    }

    protected function stringValue(mixed $value): string
    {
        if (is_array($value)) {
            $value = $value['text'] ?? $value['label'] ?? $value['name'] ?? '';
        }

        return trim((string) $value);
    }

    protected function publicUrl(mixed $value): string
    {
        $url = $this->stringValue($value);

        if ($url === '' || Str::startsWith($url, ['http://', 'https://', '//', '/', 'data:'])) {
            return $url;
        }

        return asset($url);
    }
}
