<?php

namespace Platform\ThemeDefault\ViewModels;

use Illuminate\Support\Str;
use Platform\ExperienceCms\Contracts\HeaderResolverContract;
use Platform\ExperienceCms\Contracts\SiteSettingsResolverContract;
use Platform\ExperienceCms\Models\Menu;
use Platform\ExperienceCms\Models\MenuItem;
use Throwable;

class StorefrontHeaderViewModel
{
    public function __construct(
        protected HeaderResolverContract $headers,
        protected SiteSettingsResolverContract $siteSettings,
    ) {}

    public function build(array $fallbackLinks = []): array
    {
        $settings = $this->headerSettings();
        $identity = $this->siteIdentity();
        $channel = core()->getCurrentChannel();

        $features = is_array($settings['features'] ?? null) ? $settings['features'] : [];

        return [
            'brandName' => $this->stringValue(
                $settings['brand_name']
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
            'announcement' => $this->announcementPayload($settings['announcement'] ?? null, $identity['announcement'] ?? null),
            'features' => [
                'show_search' => (bool) ($features['show_search'] ?? true),
                'show_account' => (bool) ($features['show_account'] ?? true),
                'show_cart' => (bool) ($features['show_cart'] ?? true),
                'sticky' => (bool) ($features['sticky'] ?? false),
            ],
            'variant' => $this->stringValue($settings['variant'] ?? 'classic'),
            'links' => $this->linksPayload($settings, $fallbackLinks),
            'accountUrl' => auth()->guard('customer')->check()
                ? route('shop.customers.account.index')
                : route('shop.customer.session.index'),
            'cartUrl' => route('shop.checkout.cart.index'),
            'searchUrl' => route('shop.search.index'),
            'homeUrl' => route('shop.home.index'),
        ];
    }

    protected function headerSettings(): array
    {
        try {
            $settings = $this->headers->resolve()?->settings_json;

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

    protected function announcementPayload(mixed $announcement, mixed $fallback = null): array
    {
        if (is_array($announcement)) {
            $text = $this->stringValue($announcement['text'] ?? '');

            return [
                'enabled' => (bool) ($announcement['enabled'] ?? filled($text)),
                'text' => $text,
                'link' => $this->stringValue($announcement['link'] ?? ''),
            ];
        }

        $text = $this->stringValue($announcement ?: $fallback);

        return [
            'enabled' => filled($text),
            'text' => $text,
            'link' => '',
        ];
    }

    protected function linksPayload(array $settings, array $fallbackLinks): array
    {
        $menuId = (int) data_get($settings, 'navigation.menu_id');

        if ($menuId > 0) {
            $menuLinks = $this->menuLinks($menuId);

            if ($menuLinks !== []) {
                return $menuLinks;
            }
        }

        if (is_array($settings['links'] ?? null)) {
            $legacyLinks = collect($settings['links'])
                ->map(fn (mixed $link): array => $this->normalizeLink(is_array($link) ? $link : []))
                ->filter(fn (array $link): bool => filled($link['label']))
                ->values()
                ->all();

            if ($legacyLinks !== []) {
                return $legacyLinks;
            }
        }

        return collect($fallbackLinks)
            ->map(fn (mixed $link): array => $this->normalizeLink(is_array($link) ? $link : []))
            ->filter(fn (array $link): bool => filled($link['label']))
            ->values()
            ->all();
    }

    protected function menuLinks(int $menuId): array
    {
        try {
            $menu = Menu::query()
                ->whereKey($menuId)
                ->where('is_active', true)
                ->with(['items' => fn ($query) => $query
                    ->whereNull('parent_id')
                    ->where('is_active', true)
                    ->orderBy('sort_order')])
                ->first();
        } catch (Throwable) {
            return [];
        }

        if (! $menu) {
            return [];
        }

        return $menu->items
            ->map(fn (MenuItem $item): array => [
                'label' => $item->title,
                'url' => $item->target ?: '#',
                'open_in_new_tab' => (bool) data_get($item->settings_json, 'open_in_new_tab', false),
            ])
            ->filter(fn (array $link): bool => filled($link['label']))
            ->values()
            ->all();
    }

    protected function normalizeLink(array $link): array
    {
        return [
            'label' => $this->stringValue($link['label'] ?? $link['title'] ?? 'Link'),
            'url' => $this->stringValue($link['url'] ?? $link['target'] ?? '#') ?: '#',
            'open_in_new_tab' => (bool) ($link['open_in_new_tab'] ?? $link['new_tab'] ?? false),
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
