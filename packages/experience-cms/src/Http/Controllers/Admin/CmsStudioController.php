<?php

namespace Platform\ExperienceCms\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Platform\ExperienceCms\Models\FooterConfig;
use Platform\ExperienceCms\Models\HeaderConfig;
use Platform\ExperienceCms\Models\Menu;

class CmsStudioController extends Controller
{
    private const AREAS = [
        'header',
        'footer',
        'navigation',
        'homepage-sections',
        'reusable-blocks',
        'static-content',
        'settings',
    ];

    public function index(Request $request): View
    {
        $area = $this->selectedArea($request);
        $menus = $this->menuOptions();
        $editor = $this->editorPayload($area, $menus);

        return view('experience-cms::admin.cms.studio.index', [
            'area' => $area,
            'navigationGroups' => $this->navigationGroups($area),
            'editor' => $editor,
            'preview' => $this->previewPayload($area, $editor, $menus),
            'previewStorefrontUrl' => $this->previewStorefrontUrl(),
            'canSave' => in_array($area, ['header', 'footer'], true) && ($editor['storage_available'] ?? false),
        ]);
    }

    public function updateHeader(Request $request): RedirectResponse
    {
        $storageColumn = $this->settingsStorageColumn(HeaderConfig::class);

        if (! $storageColumn) {
            return redirect()
                ->route('admin.cms.index', ['area' => 'header'])
                ->with('error', 'Header settings storage is not available.');
        }

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'logo_url' => ['nullable', 'string', 'max:2048'],
            'announcement_enabled' => ['boolean'],
            'announcement_text' => ['nullable', 'string', 'max:255'],
            'announcement_link' => ['nullable', 'string', 'max:2048'],
            'menu_id' => ['nullable', 'integer'],
            'show_search' => ['boolean'],
            'show_account' => ['boolean'],
            'show_cart' => ['boolean'],
            'sticky' => ['boolean'],
            'variant' => ['required', Rule::in(['classic', 'centered', 'minimal'])],
        ]);

        $headerConfig = $this->defaultHeaderConfig();

        if (! $headerConfig) {
            return redirect()
                ->route('admin.cms.index', ['area' => 'header'])
                ->with('error', 'Header settings storage is not available.');
        }

        $settings = [
            'name' => $validated['name'] ?? 'Default Header',
            'logo_url' => $validated['logo_url'] ?? null,
            'announcement' => [
                'enabled' => $request->boolean('announcement_enabled'),
                'text' => $validated['announcement_text'] ?? null,
                'link' => $validated['announcement_link'] ?? null,
            ],
            'navigation' => [
                'menu_id' => filled($validated['menu_id'] ?? null) ? (int) $validated['menu_id'] : null,
            ],
            'features' => [
                'show_search' => $request->boolean('show_search'),
                'show_account' => $request->boolean('show_account'),
                'show_cart' => $request->boolean('show_cart'),
                'sticky' => $request->boolean('sticky'),
            ],
            'variant' => $validated['variant'],
        ];

        $this->saveConfigSettings($headerConfig, $storageColumn, $settings, 'studio_header');

        return redirect()
            ->route('admin.cms.index', ['area' => 'header'])
            ->with('success', 'Header settings saved.');
    }

    public function updateFooter(Request $request): RedirectResponse
    {
        $storageColumn = $this->settingsStorageColumn(FooterConfig::class);

        if (! $storageColumn) {
            return redirect()
                ->route('admin.cms.index', ['area' => 'footer'])
                ->with('error', 'Footer settings storage is not available.');
        }

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'logo_url' => ['nullable', 'string', 'max:2048'],
            'newsletter_enabled' => ['boolean'],
            'newsletter_heading' => ['nullable', 'string', 'max:255'],
            'newsletter_text' => ['nullable', 'string', 'max:500'],
            'contact_email' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:100'],
            'social_facebook' => ['nullable', 'string', 'max:2048'],
            'social_instagram' => ['nullable', 'string', 'max:2048'],
            'social_x' => ['nullable', 'string', 'max:2048'],
            'copyright_text' => ['nullable', 'string', 'max:255'],
            'variant' => ['required', Rule::in(['simple', 'multi_column', 'minimal'])],
        ]);

        $footerConfig = $this->defaultFooterConfig();

        if (! $footerConfig) {
            return redirect()
                ->route('admin.cms.index', ['area' => 'footer'])
                ->with('error', 'Footer settings storage is not available.');
        }

        $settings = [
            'name' => $validated['name'] ?? 'Default Footer',
            'logo_url' => $validated['logo_url'] ?? null,
            'newsletter' => [
                'enabled' => $request->boolean('newsletter_enabled'),
                'heading' => $validated['newsletter_heading'] ?? null,
                'text' => $validated['newsletter_text'] ?? null,
            ],
            'contact' => [
                'email' => $validated['contact_email'] ?? null,
                'phone' => $validated['contact_phone'] ?? null,
            ],
            'social' => [
                'facebook' => $validated['social_facebook'] ?? null,
                'instagram' => $validated['social_instagram'] ?? null,
                'x' => $validated['social_x'] ?? null,
            ],
            'copyright_text' => $validated['copyright_text'] ?? null,
            'variant' => $validated['variant'],
        ];

        $this->saveConfigSettings($footerConfig, $storageColumn, $settings, 'studio_footer');

        return redirect()
            ->route('admin.cms.index', ['area' => 'footer'])
            ->with('success', 'Footer settings saved.');
    }

    private function selectedArea(Request $request): string
    {
        $area = $request->route('area') ?: $request->query('area', 'header');

        return in_array($area, self::AREAS, true) ? $area : 'header';
    }

    private function navigationGroups(string $activeArea): array
    {
        $groups = [
            [
                'title' => 'Website Layout',
                'items' => [
                    ['key' => 'header', 'label' => 'Header'],
                    ['key' => 'footer', 'label' => 'Footer'],
                    ['key' => 'navigation', 'label' => 'Navigation'],
                ],
            ],
            [
                'title' => 'Homepage',
                'items' => [
                    ['key' => 'homepage-sections', 'label' => 'Homepage Sections'],
                    ['key' => 'reusable-blocks', 'label' => 'Reusable Blocks'],
                ],
            ],
            [
                'title' => 'Content',
                'items' => [
                    ['key' => 'static-content', 'label' => 'Static Content'],
                ],
            ],
            [
                'title' => 'Configuration',
                'items' => [
                    ['key' => 'settings', 'label' => 'Site Settings'],
                ],
            ],
        ];

        return array_map(function (array $group) use ($activeArea): array {
            $group['items'] = array_map(function (array $item) use ($activeArea): array {
                return [
                    ...$item,
                    'active' => $item['key'] === $activeArea,
                    'url' => route('admin.cms.index', ['area' => $item['key']]),
                ];
            }, $group['items']);

            return $group;
        }, $groups);
    }

    private function editorPayload(string $area, array $menus): array
    {
        return match ($area) {
            'header' => $this->headerEditorPayload($menus),
            'footer' => $this->footerEditorPayload(),
            'navigation' => $this->placeholderPayload(
                'Navigation',
                'Navigation editing will be managed inside CMS Studio. Menus will control header, footer, and mobile navigation.'
            ),
            'homepage-sections' => $this->placeholderPayload(
                'Homepage Sections',
                'Add, reorder, enable, disable, preview, and publish theme-supported homepage sections.',
                'Sections are predefined by the active theme. Admins cannot create arbitrary layouts.'
            ),
            'reusable-blocks' => $this->placeholderPayload(
                'Reusable Blocks',
                'Manage reusable structured content blocks used by homepage sections, header, footer, and static content.'
            ),
            'static-content' => $this->placeholderPayload(
                'Static Content',
                'Manage landing, static, and policy content. Product, category, cart, checkout, and customer pages are not edited here.'
            ),
            'settings' => $this->placeholderPayload(
                'Site Settings',
                'Manage global website identity, SEO defaults, contact details, trust badges, and shared storefront content.'
            ),
            default => $this->headerEditorPayload($menus),
        };
    }

    private function headerEditorPayload(array $menus): array
    {
        $config = $this->defaultHeaderConfig();
        $storageColumn = $this->settingsStorageColumn(HeaderConfig::class);
        $settings = $this->settingsFromConfig($config, $storageColumn);

        return [
            'type' => 'header',
            'title' => 'Header Builder',
            'description' => 'Edit the global storefront header using safe structured controls.',
            'form_action' => route('admin.cms.header.update'),
            'storage_available' => (bool) $storageColumn,
            'storage_error' => $storageColumn ? null : 'Header settings storage is not available.',
            'menus' => $menus,
            'variants' => [
                'classic' => 'Classic',
                'centered' => 'Centered Logo',
                'minimal' => 'Minimal',
            ],
            'values' => [
                'name' => $settings['name'] ?? 'Default Header',
                'logo_url' => $settings['logo_url'] ?? null,
                'announcement_enabled' => (bool) data_get($settings, 'announcement.enabled', false),
                'announcement_text' => data_get($settings, 'announcement.text'),
                'announcement_link' => data_get($settings, 'announcement.link'),
                'menu_id' => data_get($settings, 'navigation.menu_id'),
                'show_search' => (bool) data_get($settings, 'features.show_search', true),
                'show_account' => (bool) data_get($settings, 'features.show_account', true),
                'show_cart' => (bool) data_get($settings, 'features.show_cart', true),
                'sticky' => (bool) data_get($settings, 'features.sticky', false),
                'variant' => $settings['variant'] ?? 'classic',
            ],
        ];
    }

    private function footerEditorPayload(): array
    {
        $config = $this->defaultFooterConfig();
        $storageColumn = $this->settingsStorageColumn(FooterConfig::class);
        $settings = $this->settingsFromConfig($config, $storageColumn);

        return [
            'type' => 'footer',
            'title' => 'Footer Builder',
            'description' => 'Edit the global storefront footer using safe structured controls.',
            'form_action' => route('admin.cms.footer.update'),
            'storage_available' => (bool) $storageColumn,
            'storage_error' => $storageColumn ? null : 'Footer settings storage is not available.',
            'variants' => [
                'simple' => 'Simple',
                'multi_column' => 'Multi Column',
                'minimal' => 'Minimal',
            ],
            'values' => [
                'name' => $settings['name'] ?? 'Default Footer',
                'logo_url' => $settings['logo_url'] ?? null,
                'newsletter_enabled' => (bool) data_get($settings, 'newsletter.enabled', false),
                'newsletter_heading' => data_get($settings, 'newsletter.heading'),
                'newsletter_text' => data_get($settings, 'newsletter.text'),
                'contact_email' => data_get($settings, 'contact.email'),
                'contact_phone' => data_get($settings, 'contact.phone'),
                'social_facebook' => data_get($settings, 'social.facebook'),
                'social_instagram' => data_get($settings, 'social.instagram'),
                'social_x' => data_get($settings, 'social.x'),
                'copyright_text' => $settings['copyright_text'] ?? 'Copyright '.now()->year.' Storefront. All rights reserved.',
                'variant' => $settings['variant'] ?? 'simple',
            ],
        ];
    }

    private function placeholderPayload(string $title, string $text, ?string $note = null): array
    {
        return [
            'type' => 'placeholder',
            'title' => $title,
            'description' => $text,
            'note' => $note,
            'storage_available' => false,
        ];
    }

    private function previewPayload(string $area, array $editor, array $menus): array
    {
        $values = $editor['values'] ?? [];

        return match ($area) {
            'header' => [
                'type' => 'header',
                'title' => 'Header Preview',
                'values' => $values,
                'navigation_labels' => $this->navigationLabels($values['menu_id'] ?? null, $menus),
            ],
            'footer' => [
                'type' => 'footer',
                'title' => 'Footer Preview',
                'values' => $values,
            ],
            default => [
                'type' => 'placeholder',
                'title' => $editor['title'].' Preview',
                'description' => 'This Studio area will preview structured website content here as the workflow is implemented.',
            ],
        };
    }

    private function defaultHeaderConfig(): ?HeaderConfig
    {
        if (! $this->modelTableExists(HeaderConfig::class) || ! $this->settingsStorageColumn(HeaderConfig::class)) {
            return null;
        }

        return HeaderConfig::query()->where('is_default', true)->first()
            ?? HeaderConfig::query()->orderBy('id')->first()
            ?? HeaderConfig::query()->create($this->newConfigAttributes(HeaderConfig::class, 'studio_header'));
    }

    private function defaultFooterConfig(): ?FooterConfig
    {
        if (! $this->modelTableExists(FooterConfig::class) || ! $this->settingsStorageColumn(FooterConfig::class)) {
            return null;
        }

        return FooterConfig::query()->where('is_default', true)->first()
            ?? FooterConfig::query()->orderBy('id')->first()
            ?? FooterConfig::query()->create($this->newConfigAttributes(FooterConfig::class, 'studio_footer'));
    }

    private function saveConfigSettings(Model $config, string $storageColumn, array $settings, string $fallbackCode): void
    {
        $table = $config->getTable();

        if (Schema::hasColumn($table, 'is_default')) {
            $config::query()
                ->whereKeyNot($config->getKey())
                ->update(['is_default' => false]);
        }

        $attributes = [
            $storageColumn => $this->storageValue($storageColumn, $settings),
        ];

        if (Schema::hasColumn($table, 'code') && blank($config->getAttribute('code'))) {
            $attributes['code'] = $fallbackCode;
        }

        if (Schema::hasColumn($table, 'is_default')) {
            $attributes['is_default'] = true;
        }

        $config->forceFill($attributes)->save();
    }

    private function newConfigAttributes(string $modelClass, string $code): array
    {
        $model = new $modelClass;
        $table = $model->getTable();
        $storageColumn = $this->settingsStorageColumn($modelClass);
        $attributes = [];

        if (Schema::hasColumn($table, 'code')) {
            $attributes['code'] = $code;
        }

        if ($storageColumn) {
            $attributes[$storageColumn] = $this->storageValue($storageColumn, []);
        }

        if (Schema::hasColumn($table, 'is_default')) {
            $attributes['is_default'] = true;
        }

        return $attributes;
    }

    private function settingsStorageColumn(string $modelClass): ?string
    {
        if (! $this->modelTableExists($modelClass)) {
            return null;
        }

        $table = (new $modelClass)->getTable();

        foreach (['settings_json', 'settings'] as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    private function settingsFromConfig(?Model $config, ?string $storageColumn): array
    {
        if (! $config || ! $storageColumn) {
            return [];
        }

        $settings = $config->getAttribute($storageColumn);

        if (is_string($settings)) {
            $settings = json_decode($settings, true);
        }

        return is_array($settings) ? $settings : [];
    }

    private function storageValue(string $storageColumn, array $settings): array|string
    {
        return $storageColumn === 'settings_json'
            ? $settings
            : json_encode($settings, JSON_UNESCAPED_SLASHES);
    }

    private function menuOptions(): array
    {
        if (! $this->modelTableExists(Menu::class)) {
            return [];
        }

        return Menu::query()
            ->with('items')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Menu $menu): array => [
                'id' => $menu->getKey(),
                'name' => $menu->name,
                'items' => $menu->items
                    ->where('is_active', true)
                    ->sortBy('sort_order')
                    ->pluck('title')
                    ->values()
                    ->all(),
            ])
            ->all();
    }

    private function navigationLabels(mixed $menuId, array $menus): array
    {
        $menu = collect($menus)->first(fn (array $menu): bool => (string) $menu['id'] === (string) $menuId);

        return $menu['items'] ?? ['Home', 'Shop', 'Contact'];
    }

    private function modelTableExists(string $modelClass): bool
    {
        if (! class_exists($modelClass)) {
            return false;
        }

        $model = new $modelClass;

        return $model instanceof Model && Schema::hasTable($model->getTable());
    }

    private function previewStorefrontUrl(): string
    {
        if (Route::has('shop.home.index')) {
            return route('shop.home.index');
        }

        return url('/');
    }
}
