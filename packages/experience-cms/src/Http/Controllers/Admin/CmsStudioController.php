<?php

namespace Platform\ExperienceCms\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Platform\ExperienceCms\Models\FooterConfig;
use Platform\ExperienceCms\Models\HeaderConfig;
use Platform\ExperienceCms\Models\Menu;
use Platform\ExperienceCms\Models\Page;
use Platform\ExperienceCms\Models\PageSection;
use Platform\ExperienceCms\Models\SectionType;
use Platform\ExperienceCms\Models\SiteSetting;
use Platform\ExperienceCms\Models\Template;
use Platform\ExperienceCms\Services\HomepageSectionEditor;
use Platform\ExperienceCms\Services\MenuEditor;
use Platform\ExperienceCms\Services\SectionTypeRegistry;

class CmsStudioController extends Controller
{
    private const AREAS = [
        'header',
        'footer',
        'navigation',
        'homepage',
    ];

    private const AREA_ALIASES = [
        'homepage-sections' => 'homepage',
    ];

    private const HOMEPAGE_SECTION_CODES = [
        'hero',
        'hero_banner',
        'hero_slider',
        'promo_strip',
        'category_grid',
        'featured_products',
        'best_sellers',
        'new_arrivals',
        'rich_text',
        'trust_badges',
        'faq_block',
    ];

    private const EDITABLE_HOMEPAGE_SECTION_CODES = [
        'hero',
    ];

    public function index(Request $request): View
    {
        $area = $this->selectedArea($request);
        $menus = $this->menuOptions();
        $editor = $this->editorPayload($request, $area, $menus);

        return view('experience-cms::admin.cms.studio.index', [
            'area' => $area,
            'navigationGroups' => $this->navigationGroups($area),
            'editor' => $editor,
            'preview' => $this->previewPayload($area, $editor, $menus),
            'previewStorefrontUrl' => $this->previewStorefrontUrl(),
            'canSave' => in_array($area, ['header', 'footer', 'navigation', 'homepage'], true) && ($editor['storage_available'] ?? false),
        ]);
    }

    public function settings(): View
    {
        return view('experience-cms::admin.site-settings.index', [
            'summary' => $this->siteSettingsSummary(),
            'groups' => $this->siteSettingsGroups(),
            'previewStorefrontUrl' => $this->previewStorefrontUrl(),
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
            'name' => ['required', 'string', 'max:255'],
            'logo_url' => ['nullable', 'string', 'max:2048'],
            'logo_file' => ['nullable', File::image()->max('2mb')],
            'announcement_enabled' => ['boolean'],
            'announcement_text' => ['nullable', 'string', 'max:255'],
            'announcement_link' => ['nullable', 'url', 'max:2048'],
            'menu_id' => ['nullable', 'integer', Rule::exists('menus', 'id')->where('is_active', true)],
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

        $logoUrl = $this->storedCmsLogoUrl($request->file('logo_file'), $validated['logo_url'] ?? null, 'cms/header');

        $settings = [
            'name' => $validated['name'] ?? 'Default Header',
            'logo_url' => $logoUrl,
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

    private function storedCmsLogoUrl(mixed $uploadedLogo, ?string $existingLogoUrl, string $directory): ?string
    {
        if ($uploadedLogo instanceof UploadedFile && $uploadedLogo->isValid()) {
            return Storage::disk('public')->url($uploadedLogo->store($directory, 'public'));
        }

        return filled($existingLogoUrl) ? trim($existingLogoUrl) : null;
    }

    private function footerColumnsFromRequest(array $columns): array
    {
        $menuIds = collect($columns)
            ->pluck('menu_id')
            ->filter()
            ->map(fn (mixed $menuId): int => (int) $menuId)
            ->unique()
            ->values();

        if ($menuIds->isEmpty()) {
            return [];
        }

        $menus = Menu::query()
            ->whereIn('id', $menuIds)
            ->where('is_active', true)
            ->pluck('name', 'id');

        return collect($columns)
            ->take(4)
            ->map(function (array $column, int $index) use ($menus): ?array {
                if (! filter_var($column['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                    return null;
                }

                $menuId = (int) ($column['menu_id'] ?? 0);

                if ($menuId <= 0 || ! $menus->has($menuId)) {
                    return null;
                }

                $title = trim((string) ($column['title'] ?? ''));

                return [
                    'title' => $title !== '' ? $title : $menus->get($menuId),
                    'menu_id' => $menuId,
                    'enabled' => true,
                    'sort_order' => (int) ($column['sort_order'] ?? $index + 1),
                ];
            })
            ->filter()
            ->sortBy('sort_order')
            ->values()
            ->all();
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
            'name' => ['required', 'string', 'max:255'],
            'logo_url' => ['nullable', 'string', 'max:2048'],
            'logo_file' => ['nullable', File::image()->max('2mb')],
            'footer_description' => ['nullable', 'string', 'max:500'],
            'menu_id' => ['nullable', 'integer', Rule::exists('menus', 'id')->where('is_active', true)],
            'footer_columns' => ['nullable', 'array', 'max:4'],
            'footer_columns.*.enabled' => ['boolean'],
            'footer_columns.*.title' => ['nullable', 'string', 'max:80'],
            'footer_columns.*.menu_id' => ['nullable', 'integer', Rule::exists('menus', 'id')->where('is_active', true)],
            'footer_columns.*.sort_order' => ['nullable', 'integer', 'min:1', 'max:4'],
            'newsletter_enabled' => ['boolean'],
            'newsletter_heading' => ['nullable', 'string', 'max:255'],
            'newsletter_text' => ['nullable', 'string', 'max:500'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:100'],
            'social_facebook' => ['nullable', 'url', 'max:2048'],
            'social_instagram' => ['nullable', 'url', 'max:2048'],
            'social_x' => ['nullable', 'url', 'max:2048'],
            'social_youtube' => ['nullable', 'url', 'max:2048'],
            'social_tiktok' => ['nullable', 'url', 'max:2048'],
            'copyright_text' => ['nullable', 'string', 'max:255'],
            'variant' => ['required', Rule::in(['simple', 'multi_column', 'minimal'])],
        ]);

        $footerConfig = $this->defaultFooterConfig();

        if (! $footerConfig) {
            return redirect()
                ->route('admin.cms.index', ['area' => 'footer'])
                ->with('error', 'Footer settings storage is not available.');
        }

        $logoUrl = $this->storedCmsLogoUrl($request->file('logo_file'), $validated['logo_url'] ?? null, 'cms/footer');
        $footerColumns = $this->footerColumnsFromRequest($validated['footer_columns'] ?? []);
        $legacyMenuId = data_get($footerColumns, '0.menu_id')
            ?? (! empty($validated['menu_id']) ? (int) $validated['menu_id'] : null);

        $settings = [
            'name' => $validated['name'] ?? 'Default Footer',
            'logo_url' => $logoUrl,
            'description' => $validated['footer_description'] ?? null,
            'navigation' => [
                'menu_id' => $legacyMenuId,
                'columns' => $footerColumns,
            ],
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
                'youtube' => $validated['social_youtube'] ?? null,
                'tiktok' => $validated['social_tiktok'] ?? null,
            ],
            'copyright_text' => $validated['copyright_text'] ?? null,
            'variant' => $validated['variant'],
        ];

        $this->saveConfigSettings($footerConfig, $storageColumn, $settings, 'studio_footer');

        return redirect()
            ->route('admin.cms.index', ['area' => 'footer'])
            ->with('success', 'Footer settings saved.');
    }

    public function updateNavigation(Request $request, MenuEditor $menuEditor): RedirectResponse
    {
        if (! $this->modelTableExists(Menu::class)) {
            return redirect()
                ->route('admin.cms.index', ['area' => 'navigation'])
                ->with('error', 'Navigation menu storage is not available.');
        }

        $validated = $request->validate([
            'menu_id' => ['nullable', 'integer', Rule::exists('menus', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'location' => ['required', Rule::in(array_keys($this->menuLocations()))],
            'is_active' => ['boolean'],
            'items' => ['nullable', 'array', 'max:30'],
            'items.*.title' => ['nullable', 'string', 'max:255'],
            'items.*.type' => ['nullable', Rule::in(array_keys($this->menuItemTypes()))],
            'items.*.target' => ['nullable', 'string', 'max:2048'],
            'items.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'items.*.is_active' => ['boolean'],
            'items.*.open_in_new_tab' => ['boolean'],
        ]);

        $items = collect($validated['items'] ?? [])
            ->map(fn (array $item): array => [
                'title' => trim((string) ($item['title'] ?? '')),
                'type' => $item['type'] ?? 'url',
                'target' => trim((string) ($item['target'] ?? '')),
                'sort_order' => (int) ($item['sort_order'] ?? 0),
                'is_active' => (bool) ($item['is_active'] ?? false),
                'open_in_new_tab' => (bool) ($item['open_in_new_tab'] ?? false),
            ])
            ->filter(fn (array $item): bool => $item['title'] !== '' || $item['target'] !== '')
            ->values();

        $items->each(function (array $item, int $index): void {
            validator($item, [
                'title' => ['required', 'string', 'max:255'],
                'type' => ['required', Rule::in(array_keys($this->menuItemTypes()))],
                'target' => ['required', 'string', 'max:2048'],
                'sort_order' => ['integer', 'min:0', 'max:10000'],
            ], [], [
                'title' => 'item '.($index + 1).' label',
                'target' => 'item '.($index + 1).' URL',
            ])->validate();
        });

        $attributes = [
            'name' => $validated['name'],
            'location' => $validated['location'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ];

        $menuItems = $items
            ->map(fn (array $item, int $index): array => [
                'id' => null,
                'title' => $item['title'],
                'type' => $item['type'],
                'target' => $item['target'],
                'sort_order' => $item['sort_order'] ?: $index + 1,
                'settings_json' => [
                    'open_in_new_tab' => $item['open_in_new_tab'],
                ],
                'is_active' => $item['is_active'],
            ])
            ->all();

        $menu = filled($validated['menu_id'] ?? null)
            ? $menuEditor->update(Menu::query()->findOrFail($validated['menu_id']), $attributes, $menuItems)
            : $menuEditor->create(['code' => $this->uniqueMenuCode($validated['name']), ...$attributes], $menuItems);

        return redirect()
            ->route('admin.cms.index', ['area' => 'navigation', 'menu' => $menu->getKey()])
            ->with('success', 'Navigation menu saved.');
    }

    public function updateHomepage(Request $request, HomepageSectionEditor $homepageSections): RedirectResponse
    {
        $this->ensureHomepageSectionTypes();

        $homepage = $this->homepagePageForEditing();

        if (! $homepage) {
            return redirect()
                ->route('admin.cms.index', ['area' => 'homepage'])
                ->with('error', 'Homepage section storage is not available.');
        }

        $validated = $request->validate([
            'sections' => ['nullable', 'array', 'max:24'],
            'sections.*.id' => ['nullable', 'integer'],
            'sections.*.section_code' => ['nullable', 'string'],
            'sections.*.title' => ['nullable', 'string', 'max:255'],
            'sections.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'sections.*.is_active' => ['boolean'],
            'sections.*.settings' => ['nullable', 'array'],
            'sections.*.settings.mode' => ['nullable', Rule::in(['static', 'slider'])],
            'sections.*.settings.eyebrow' => ['nullable', 'string', 'max:120'],
            'sections.*.settings.headline' => ['nullable', 'string', 'max:255'],
            'sections.*.settings.body' => ['nullable', 'string', 'max:1000'],
            'sections.*.settings.primary_cta_label' => ['nullable', 'string', 'max:80'],
            'sections.*.settings.primary_cta_url' => ['nullable', 'string', 'max:2048'],
            'sections.*.settings.secondary_cta_label' => ['nullable', 'string', 'max:80'],
            'sections.*.settings.secondary_cta_url' => ['nullable', 'string', 'max:2048'],
            'sections.*.settings.content' => ['nullable', 'string', 'max:5000'],
            'sections.*.settings.slides' => ['nullable', 'array', 'max:5'],
            'sections.*.settings.slides.*.enabled' => ['nullable', 'boolean'],
            'sections.*.settings.slides.*.current_image' => ['nullable', 'string', 'max:2048'],
            'sections.*.settings.slides.*.image_file' => ['nullable', File::image()->max('4mb')],
            'sections.*.settings.slides.*.title' => ['nullable', 'string', 'max:120'],
            'sections.*.settings.slides.*.headline' => ['nullable', 'string', 'max:255'],
            'sections.*.settings.slides.*.body' => ['nullable', 'string', 'max:1000'],
            'sections.*.settings.slides.*.primary_cta_label' => ['nullable', 'string', 'max:80'],
            'sections.*.settings.slides.*.primary_cta_url' => ['nullable', 'string', 'max:2048'],
            'sections.*.settings.slides.*.secondary_cta_label' => ['nullable', 'string', 'max:80'],
            'sections.*.settings.slides.*.secondary_cta_url' => ['nullable', 'string', 'max:2048'],
            'sections.*.settings.slides.*.link' => ['nullable', 'string', 'max:2048'],
        ]);

        $existingSections = $homepage->sections()
            ->with('sectionType')
            ->get()
            ->keyBy('id');

        $submittedSectionIds = collect($validated['sections'] ?? [])
            ->pluck('id')
            ->filter(fn ($id): bool => filled($id))
            ->map(fn ($id): int => (int) $id);

        $unknownSectionIds = $submittedSectionIds
            ->diff($existingSections->keys()->map(fn ($id): int => (int) $id))
            ->values();

        if ($unknownSectionIds->isNotEmpty()) {
            throw ValidationException::withMessages([
                'sections' => 'One or more submitted homepage sections do not belong to this homepage.',
            ]);
        }

        $sections = collect($validated['sections'] ?? [])
            ->map(function (array $section) use ($existingSections): ?array {
                $existingSection = filled($section['id'] ?? null)
                    ? $existingSections->get((int) $section['id'])
                    : null;

                $sectionCode = $this->normalizedHomepageSectionCode($section['section_code'] ?? $existingSection?->sectionType?->code);
                $settings = $section['settings'] ?? [];
                $hasContent = filled($section['title'] ?? null)
                    || filled($sectionCode)
                    || collect($settings)->filter(fn ($value): bool => filled($value))->isNotEmpty();

                if (! $existingSection && ! $hasContent) {
                    return null;
                }

                if (! $sectionCode) {
                    return null;
                }

                $isEditable = in_array($sectionCode, self::EDITABLE_HOMEPAGE_SECTION_CODES, true);

                return [
                    'id' => $existingSection?->getKey(),
                    'is_new' => ! $existingSection,
                    'section_code' => $sectionCode,
                    'title' => trim((string) ($section['title'] ?? $existingSection?->title ?? '')),
                    'sort_order' => (int) ($section['sort_order'] ?? $existingSection?->sort_order ?? 0),
                    'settings_json' => $isEditable
                        ? $this->homepageSectionSettingsFromRequest($sectionCode, $settings)
                        : ($existingSection?->settings_json ?? []),
                    'visibility_rules_json' => $existingSection?->visibility_rules_json ?? [],
                    'data_source_type' => $existingSection?->data_source_type,
                    'data_source_payload_json' => $existingSection?->data_source_payload_json ?? [],
                    'is_active' => (bool) ($section['is_active'] ?? false),
                ];
            })
            ->filter()
            ->sortBy('sort_order')
            ->values();

        $sections->each(function (array $section, int $index): void {
            if (! in_array($section['section_code'], self::HOMEPAGE_SECTION_CODES, true)) {
                throw ValidationException::withMessages([
                    "sections.$index.section_code" => 'This section type is not supported on the homepage.',
                ]);
            }

            if (($section['is_new'] ?? false) && ! in_array($section['section_code'], self::EDITABLE_HOMEPAGE_SECTION_CODES, true)) {
                throw ValidationException::withMessages([
                    "sections.$index.section_code" => 'This section type is preserved by the theme and cannot be added manually yet.',
                ]);
            }

            validator($section['settings_json'], $this->homepageSectionValidationRules($section['section_code']), [], [
                'headline' => 'section '.($index + 1).' headline',
                'content' => 'section '.($index + 1).' content',
            ])->validate();
        });

        $homepageSections->sync($homepage, $sections->all());

        return redirect()
            ->route('admin.cms.index', ['area' => 'homepage'])
            ->with('success', 'Homepage sections saved.');
    }

    private function selectedArea(Request $request): string
    {
        $area = $request->route('area') ?: $request->query('area', 'header');
        $area = self::AREA_ALIASES[$area] ?? $area;

        return in_array($area, self::AREAS, true) ? $area : 'header';
    }

    private function navigationGroups(string $activeArea): array
    {
        $groups = [
            [
                'title' => 'CMS Studio',
                'items' => [
                    ['key' => 'header', 'label' => 'Header'],
                    ['key' => 'footer', 'label' => 'Footer'],
                    ['key' => 'navigation', 'label' => 'Navigation'],
                    ['key' => 'homepage', 'label' => 'Homepage'],
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

    private function editorPayload(Request $request, string $area, array $menus): array
    {
        return match ($area) {
            'header' => $this->headerEditorPayload($menus),
            'footer' => $this->footerEditorPayload($menus),
            'navigation' => $this->navigationEditorPayload($request),
            'homepage' => $this->homepageEditorPayload(),
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

    private function footerEditorPayload(array $menus): array
    {
        $config = $this->defaultFooterConfig();
        $storageColumn = $this->settingsStorageColumn(FooterConfig::class);
        $settings = $this->settingsFromConfig($config, $storageColumn);
        $fallbackDescription = $this->siteSettingValue('store.contact', 'description');

        return [
            'type' => 'footer',
            'title' => 'Footer Builder',
            'description' => 'Edit the global storefront footer using safe structured controls.',
            'form_action' => route('admin.cms.footer.update'),
            'storage_available' => (bool) $storageColumn,
            'storage_error' => $storageColumn ? null : 'Footer settings storage is not available.',
            'menus' => $menus,
            'variants' => [
                'simple' => 'Simple',
                'multi_column' => 'Multi Column',
                'minimal' => 'Minimal',
            ],
            'values' => [
                'name' => $settings['name'] ?? 'Default Footer',
                'logo_url' => $settings['logo_url'] ?? null,
                'footer_description' => $settings['description'] ?? $fallbackDescription,
                'menu_id' => data_get($settings, 'navigation.menu_id'),
                'footer_columns' => $this->footerColumnValues($settings, $menus),
                'newsletter_enabled' => (bool) data_get($settings, 'newsletter.enabled', false),
                'newsletter_heading' => data_get($settings, 'newsletter.heading'),
                'newsletter_text' => data_get($settings, 'newsletter.text'),
                'contact_email' => data_get($settings, 'contact.email'),
                'contact_phone' => data_get($settings, 'contact.phone'),
                'social_facebook' => data_get($settings, 'social.facebook'),
                'social_instagram' => data_get($settings, 'social.instagram'),
                'social_x' => data_get($settings, 'social.x'),
                'social_youtube' => data_get($settings, 'social.youtube'),
                'social_tiktok' => data_get($settings, 'social.tiktok'),
                'copyright_text' => $settings['copyright_text'] ?? 'Copyright '.now()->year.' Storefront. All rights reserved.',
                'variant' => $settings['variant'] ?? 'simple',
            ],
        ];
    }

    private function navigationEditorPayload(Request $request): array
    {
        if (! $this->modelTableExists(Menu::class)) {
            return [
                'type' => 'navigation',
                'title' => 'Navigation Builder',
                'description' => 'Create and edit business-friendly menus used by the storefront header and footer.',
                'form_action' => route('admin.cms.navigation.update'),
                'storage_available' => false,
                'storage_error' => 'Navigation menu storage is not available.',
                'save_label' => 'Save Menu',
                'menus' => [],
                'locations' => $this->menuLocations(),
                'item_types' => $this->menuItemTypes(),
                'values' => $this->emptyMenuPayload(),
            ];
        }

        $menus = Menu::query()
            ->withCount('items')
            ->orderByDesc('is_active')
            ->orderBy('location')
            ->orderBy('name')
            ->get();

        $selectedMenu = $this->selectedMenuForEditing($request, $menus);

        return [
            'type' => 'navigation',
            'title' => 'Navigation Builder',
            'description' => 'Create and edit business-friendly menus used by the storefront header and footer.',
            'form_action' => route('admin.cms.navigation.update'),
            'storage_available' => true,
            'storage_error' => null,
            'save_label' => 'Save Menu',
            'menus' => $menus->map(fn (Menu $menu): array => [
                'id' => $menu->getKey(),
                'name' => $menu->name,
                'location' => $menu->location,
                'location_label' => $this->menuLocations()[$menu->location] ?? Str::headline((string) $menu->location),
                'items_count' => $menu->items_count,
                'is_active' => $menu->is_active,
                'edit_url' => route('admin.cms.index', ['area' => 'navigation', 'menu' => $menu->getKey()]),
            ])->all(),
            'create_url' => route('admin.cms.index', ['area' => 'navigation', 'menu' => 'new']),
            'locations' => $this->menuLocations(),
            'item_types' => $this->menuItemTypes(),
            'values' => $selectedMenu ? $this->menuPayload($selectedMenu) : $this->emptyMenuPayload(),
        ];
    }

    private function selectedMenuForEditing(Request $request, EloquentCollection $menus): ?Menu
    {
        if ($request->query('menu') === 'new') {
            return null;
        }

        if (filled($request->query('menu'))) {
            return $menus->first(fn (Menu $menu): bool => (string) $menu->getKey() === (string) $request->query('menu'));
        }

        return $menus->first();
    }

    private function emptyMenuPayload(): array
    {
        return [
            'id' => null,
            'name' => '',
            'location' => 'header',
            'is_active' => true,
            'items' => [
                $this->emptyMenuItemPayload(1),
            ],
        ];
    }

    private function menuPayload(Menu $menu): array
    {
        $menu->loadMissing(['items' => fn ($query) => $query->whereNull('parent_id')->orderBy('sort_order')]);

        $items = $menu->items
            ->whereNull('parent_id')
            ->sortBy('sort_order')
            ->values()
            ->map(fn ($item): array => [
                'title' => $item->title,
                'type' => $item->type,
                'target' => $item->target,
                'sort_order' => $item->sort_order,
                'is_active' => $item->is_active,
                'open_in_new_tab' => (bool) data_get($item->settings_json, 'open_in_new_tab', false),
            ])
            ->all();

        return [
            'id' => $menu->getKey(),
            'name' => $menu->name,
            'location' => $menu->location,
            'is_active' => $menu->is_active,
            'items' => $items ?: [$this->emptyMenuItemPayload(1)],
        ];
    }

    private function emptyMenuItemPayload(int $sortOrder): array
    {
        return [
            'title' => '',
            'type' => 'url',
            'target' => '',
            'sort_order' => $sortOrder,
            'is_active' => true,
            'open_in_new_tab' => false,
        ];
    }

    private function menuLocations(): array
    {
        return [
            'header' => 'Header Navigation',
            'footer' => 'Footer Links',
            'mobile' => 'Mobile Navigation',
            'utility' => 'Utility Links',
        ];
    }

    private function menuItemTypes(): array
    {
        return [
            'url' => 'Custom URL',
            'page' => 'Page URL',
            'category' => 'Category URL',
        ];
    }

    private function uniqueMenuCode(string $name): string
    {
        $baseCode = Str::slug($name) ?: 'menu';
        $code = $baseCode;
        $suffix = 2;

        while (Menu::query()->where('code', $code)->exists()) {
            $code = $baseCode.'-'.$suffix;
            $suffix++;
        }

        return $code;
    }

    private function homepageEditorPayload(): array
    {
        $this->ensureHomepageSectionTypes();

        $homepage = $this->homepagePageForEditing();

        if (! $homepage || ! $this->modelTableExists(PageSection::class) || ! $this->modelTableExists(SectionType::class)) {
            return [
                'type' => 'homepage',
                'title' => 'Homepage Builder',
                'description' => 'Configure the homepage Hero while the active theme owns below-Hero content.',
                'form_action' => route('admin.cms.homepage.update'),
                'storage_available' => false,
                'storage_error' => 'Homepage section storage is not available.',
                'save_label' => 'Save Homepage',
                'values' => [
                    'page' => null,
                    'sections' => [],
                ],
                'section_types' => [],
            ];
        }

        $homepage->loadMissing(['sections.sectionType', 'sections.templateArea', 'versions']);

        return [
            'type' => 'homepage',
            'title' => 'Homepage Builder',
            'description' => 'Configure the homepage Hero while the active theme owns below-Hero content.',
            'form_action' => route('admin.cms.homepage.update'),
            'storage_available' => true,
            'storage_error' => null,
            'save_label' => 'Save Homepage',
            'preview_url' => $this->homepagePreviewUrl(),
            'section_types' => $this->editableHomepageSectionTypes(),
            'values' => [
                'page' => [
                    'title' => $homepage->title,
                    'slug' => $homepage->slug,
                    'status' => $homepage->status,
                    'updated_at' => $homepage->updated_at?->diffForHumans(),
                    'published_at' => $homepage->published_at?->diffForHumans(),
                    'preview_url' => $this->homepagePreviewUrl(),
                ],
                'sections' => $homepage->sections
                    ->sortBy('sort_order')
                    ->values()
                    ->map(fn (PageSection $section): array => $this->homepageSectionPayload($section))
                    ->filter(fn (array $section): bool => ($section['section_code'] ?? null) === 'hero')
                    ->take(1)
                    ->values()
                    ->all(),
            ],
        ];
    }

    private function homepagePageForEditing(): ?Page
    {
        if (! $this->modelTableExists(Page::class) || ! $this->modelTableExists(Template::class)) {
            return null;
        }

        $homepage = Page::query()->where('slug', 'home')->first()
            ?? Page::query()->where('type', 'homepage')->orderBy('id')->first();

        if ($homepage) {
            return $homepage;
        }

        $template = Template::query()
            ->where('code', 'homepage_default')
            ->orWhere('page_type', 'homepage')
            ->orderByDesc('is_active')
            ->orderBy('id')
            ->first();

        if (! $template) {
            return null;
        }

        return Page::query()->create([
            'title' => 'Homepage',
            'slug' => 'home',
            'type' => 'homepage',
            'template_id' => $template->getKey(),
            'settings_json' => [],
            'status' => Page::STATUS_DRAFT,
            'created_by' => auth('admin')->id(),
            'updated_by' => auth('admin')->id(),
        ]);
    }

    private function ensureHomepageSectionTypes(): void
    {
        if (! $this->modelTableExists(SectionType::class)) {
            return;
        }

        app(SectionTypeRegistry::class)
            ->all()
            ->filter(fn ($definition): bool => in_array($definition->code(), self::HOMEPAGE_SECTION_CODES, true))
            ->each(fn ($definition): SectionType => SectionType::query()->updateOrCreate(
                ['code' => $definition->code()],
                $definition->toArray()
            ));
    }

    private function homepageSectionPayload(PageSection $section): array
    {
        $storedSectionCode = (string) $section->sectionType?->code;
        $sectionCode = $this->normalizedHomepageSectionCode($storedSectionCode);
        $settings = array_replace(
            $this->homepageSectionDefaults($sectionCode),
            $this->normalizeHomepageSectionSettings($storedSectionCode, is_array($section->settings_json) ? $section->settings_json : [])
        );

        return [
            'id' => $section->getKey(),
            'section_code' => $sectionCode,
            'section_label' => $sectionCode === 'hero' ? 'Hero' : ($section->sectionType?->name ?? Str::headline($sectionCode)),
            'area_label' => $section->templateArea?->name,
            'is_editable' => in_array($sectionCode, self::EDITABLE_HOMEPAGE_SECTION_CODES, true),
            'title' => $section->title ?: $section->sectionType?->name,
            'sort_order' => $section->sort_order,
            'is_active' => $section->is_active,
            'settings' => $settings,
        ];
    }

    private function editableHomepageSectionTypes(): array
    {
        if (! $this->modelTableExists(SectionType::class)) {
            return [];
        }

        return collect(self::EDITABLE_HOMEPAGE_SECTION_CODES)
            ->map(fn (string $code): ?SectionType => SectionType::query()
                ->where('code', $code)
                ->where('is_active', true)
                ->first())
            ->filter()
            ->map(fn (SectionType $sectionType): array => [
                'code' => $sectionType->code,
                'name' => $sectionType->name,
                'category' => $sectionType->category,
                'defaults' => $this->homepageSectionDefaults($sectionType->code),
            ])
            ->values()
            ->all();
    }

    private function homepageSectionDefaults(string $sectionCode): array
    {
        return match ($sectionCode) {
            'hero' => [
                'mode' => 'static',
                'slides' => [],
            ],
            'hero_banner' => [
                'eyebrow' => '',
                'headline' => '',
                'body' => '',
                'primary_cta_label' => '',
                'primary_cta_url' => '',
                'secondary_cta_label' => '',
                'secondary_cta_url' => '',
            ],
            'hero_slider' => [
                'slides' => [],
            ],
            'promo_strip', 'rich_text' => [
                'content' => '',
            ],
            default => [],
        };
    }

    private function homepageSectionSettingsFromRequest(string $sectionCode, array $settings): array
    {
        return match ($sectionCode) {
            'hero' => [
                'mode' => in_array($settings['mode'] ?? 'static', ['static', 'slider'], true) ? $settings['mode'] : 'static',
                'slides' => $this->heroSlidesFromRequest($settings['slides'] ?? [], (string) ($settings['mode'] ?? 'static')),
            ],
            'hero_banner' => [
                'eyebrow' => trim((string) ($settings['eyebrow'] ?? '')),
                'headline' => trim((string) ($settings['headline'] ?? '')),
                'body' => trim((string) ($settings['body'] ?? '')),
                'primary_cta_label' => trim((string) ($settings['primary_cta_label'] ?? '')),
                'primary_cta_url' => trim((string) ($settings['primary_cta_url'] ?? '')),
                'secondary_cta_label' => trim((string) ($settings['secondary_cta_label'] ?? '')),
                'secondary_cta_url' => trim((string) ($settings['secondary_cta_url'] ?? '')),
            ],
            'hero_slider' => [
                'slides' => $this->heroSliderSlidesFromRequest($settings['slides'] ?? []),
            ],
            'promo_strip', 'rich_text' => [
                'content' => trim((string) ($settings['content'] ?? '')),
            ],
            default => [],
        };
    }

    private function homepageSectionValidationRules(string $sectionCode): array
    {
        return match ($sectionCode) {
            'hero' => [
                'mode' => ['required', Rule::in(['static', 'slider'])],
                'slides' => ['required', 'array', 'min:1', 'max:5'],
                'slides.*.image' => ['required', 'string', 'max:2048'],
                'slides.*.enabled' => ['nullable', 'boolean'],
                'slides.*.title' => ['nullable', 'string', 'max:120'],
                'slides.*.headline' => ['nullable', 'string', 'max:255'],
                'slides.*.body' => ['nullable', 'string', 'max:1000'],
                'slides.*.primary_cta_label' => ['nullable', 'string', 'max:80'],
                'slides.*.primary_cta_url' => ['nullable', 'string', 'max:2048'],
                'slides.*.secondary_cta_label' => ['nullable', 'string', 'max:80'],
                'slides.*.secondary_cta_url' => ['nullable', 'string', 'max:2048'],
            ],
            'hero_banner' => [
                'eyebrow' => ['nullable', 'string', 'max:120'],
                'headline' => ['required', 'string', 'max:255'],
                'body' => ['nullable', 'string', 'max:1000'],
                'primary_cta_label' => ['nullable', 'string', 'max:80'],
                'primary_cta_url' => ['nullable', 'string', 'max:2048'],
                'secondary_cta_label' => ['nullable', 'string', 'max:80'],
                'secondary_cta_url' => ['nullable', 'string', 'max:2048'],
            ],
            'hero_slider' => [
                'slides' => ['required', 'array', 'min:1', 'max:5'],
                'slides.*.image' => ['required', 'string', 'max:2048'],
                'slides.*.title' => ['nullable', 'string', 'max:120'],
                'slides.*.link' => ['nullable', 'string', 'max:2048'],
            ],
            'promo_strip', 'rich_text' => [
                'content' => ['required', 'string', 'max:5000'],
            ],
            default => [],
        };
    }

    private function normalizedHomepageSectionCode(?string $sectionCode): string
    {
        return in_array($sectionCode, ['hero_banner', 'hero_slider'], true) ? 'hero' : (string) $sectionCode;
    }

    private function normalizeHomepageSectionSettings(string $sectionCode, array $settings): array
    {
        return match ($sectionCode) {
            'hero' => [
                'mode' => in_array($settings['mode'] ?? 'static', ['static', 'slider'], true) ? $settings['mode'] : 'static',
                'slides' => $this->normalizeHeroSlides($settings['slides'] ?? []),
            ],
            'hero_banner' => [
                'mode' => 'static',
                'slides' => [[
                    'image' => trim((string) ($settings['image'] ?? '')),
                    'title' => trim((string) ($settings['title'] ?? $settings['headline'] ?? '')),
                    'headline' => trim((string) ($settings['headline'] ?? '')),
                    'body' => trim((string) ($settings['body'] ?? '')),
                    'primary_cta_label' => trim((string) ($settings['primary_cta_label'] ?? '')),
                    'primary_cta_url' => trim((string) ($settings['primary_cta_url'] ?? '')),
                    'secondary_cta_label' => trim((string) ($settings['secondary_cta_label'] ?? '')),
                    'secondary_cta_url' => trim((string) ($settings['secondary_cta_url'] ?? '')),
                ]],
            ],
            'hero_slider' => [
                'mode' => 'slider',
                'slides' => $this->normalizeHeroSlides($settings['slides'] ?? []),
            ],
            default => $settings,
        };
    }

    private function normalizeHeroSlides(array $slides): array
    {
        return collect($slides)
            ->take(5)
            ->map(fn (array $slide): array => [
                'image' => trim((string) ($slide['image'] ?? '')),
                'title' => trim((string) ($slide['title'] ?? '')),
                'headline' => trim((string) ($slide['headline'] ?? $slide['title'] ?? '')),
                'body' => trim((string) ($slide['body'] ?? '')),
                'primary_cta_label' => trim((string) ($slide['primary_cta_label'] ?? '')),
                'primary_cta_url' => trim((string) ($slide['primary_cta_url'] ?? $slide['link'] ?? '')),
                'secondary_cta_label' => trim((string) ($slide['secondary_cta_label'] ?? '')),
                'secondary_cta_url' => trim((string) ($slide['secondary_cta_url'] ?? '')),
            ])
            ->filter(fn (array $slide): bool => collect($slide)->filter(fn ($value): bool => filled($value))->isNotEmpty())
            ->values()
            ->all();
    }

    private function heroSlidesFromRequest(array $slides, string $mode = 'static'): array
    {
        return collect($slides)
            ->take($mode === 'static' ? 1 : 5)
            ->map(function (array $slide): array {
                $image = trim((string) ($slide['current_image'] ?? ''));
                $uploadedImage = $slide['image_file'] ?? null;

                if ($uploadedImage instanceof UploadedFile && $uploadedImage->isValid()) {
                    $image = 'storage/'.$uploadedImage->store('cms/homepage/hero', 'public');
                }

                return [
                    'image' => $image,
                    'title' => trim((string) ($slide['title'] ?? '')),
                    'headline' => trim((string) ($slide['headline'] ?? '')),
                    'body' => trim((string) ($slide['body'] ?? '')),
                    'primary_cta_label' => trim((string) ($slide['primary_cta_label'] ?? '')),
                    'primary_cta_url' => trim((string) ($slide['primary_cta_url'] ?? '')),
                    'secondary_cta_label' => trim((string) ($slide['secondary_cta_label'] ?? '')),
                    'secondary_cta_url' => trim((string) ($slide['secondary_cta_url'] ?? '')),
                ];
            })
            ->filter(fn (array $slide): bool => collect($slide)->filter(fn ($value): bool => filled($value))->isNotEmpty())
            ->values()
            ->all();
    }

    private function heroSliderSlidesFromRequest(array $slides): array
    {
        return collect($slides)
            ->take(5)
            ->map(function (array $slide): array {
                $image = trim((string) ($slide['current_image'] ?? ''));
                $uploadedImage = $slide['image_file'] ?? null;

                if ($uploadedImage instanceof UploadedFile && $uploadedImage->isValid()) {
                    $image = 'storage/'.$uploadedImage->store('cms/homepage/hero-slider', 'public');
                }

                return [
                    'image' => $image,
                    'title' => trim((string) ($slide['title'] ?? '')),
                    'link' => trim((string) ($slide['link'] ?? '')),
                ];
            })
            ->filter(fn (array $slide): bool => filled($slide['image']) || filled($slide['title']) || filled($slide['link']))
            ->values()
            ->all();
    }

    private function homepagePreviewUrl(): ?string
    {
        if (! Route::has('platform.storefront.home_preview')) {
            return null;
        }

        return URL::temporarySignedRoute('platform.storefront.home_preview', now()->addMinutes(30));
    }

    private function placeholderPayload(string $title, string $text, ?string $note = null, array $meta = []): array
    {
        return [
            'type' => 'placeholder',
            'title' => $title,
            'description' => $text,
            'note' => $note,
            'storage_available' => false,
            'meta' => $meta,
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
            'navigation' => [
                'type' => 'navigation',
                'title' => 'Navigation Preview',
                'values' => $editor['values'] ?? [],
            ],
            'homepage' => [
                'type' => 'homepage',
                'title' => 'Homepage Preview',
                'values' => $values,
                'preview_url' => $editor['preview_url'] ?? null,
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
                'location' => $menu->location,
                'location_label' => $this->menuLocations()[$menu->location] ?? Str::headline((string) $menu->location),
                'items' => $menu->items
                    ->where('is_active', true)
                    ->sortBy('sort_order')
                    ->pluck('title')
                    ->values()
                    ->all(),
            ])
            ->all();
    }

    private function footerColumnValues(array $settings, array $menus): array
    {
        $menuLookup = collect($menus)->keyBy(fn (array $menu): int => (int) $menu['id']);
        $columns = collect(data_get($settings, 'navigation.columns', []))
            ->map(function (mixed $column, int $index) use ($menuLookup): ?array {
                $menuId = (int) data_get($column, 'menu_id');

                if ($menuId <= 0 || ! $menuLookup->has($menuId)) {
                    return null;
                }

                $menu = $menuLookup->get($menuId);
                $title = trim((string) data_get($column, 'title', ''));

                return [
                    'enabled' => (bool) data_get($column, 'enabled', true),
                    'title' => $title !== '' ? $title : $menu['name'],
                    'menu_id' => $menuId,
                    'sort_order' => (int) data_get($column, 'sort_order', $index + 1),
                ];
            })
            ->filter()
            ->sortBy('sort_order')
            ->values();

        if ($columns->isEmpty()) {
            $legacyMenuId = (int) data_get($settings, 'navigation.menu_id');

            if ($legacyMenuId > 0 && $menuLookup->has($legacyMenuId)) {
                $menu = $menuLookup->get($legacyMenuId);
                $columns->push([
                    'enabled' => true,
                    'title' => $menu['name'],
                    'menu_id' => $legacyMenuId,
                    'sort_order' => 1,
                ]);
            }
        }

        return $this->padFooterColumnValues($columns->all());
    }

    private function padFooterColumnValues(array $columns): array
    {
        $columns = collect($columns)
            ->take(4)
            ->values()
            ->all();

        for ($index = count($columns); $index < 4; $index++) {
            $columns[] = [
                'enabled' => false,
                'title' => '',
                'menu_id' => null,
                'sort_order' => $index + 1,
            ];
        }

        return $columns;
    }

    private function menuSummary(): array
    {
        if (! $this->modelTableExists(Menu::class)) {
            return ['total' => 0, 'active' => 0, 'items' => 0, 'menus' => []];
        }

        $menus = Menu::query()
            ->withCount('items')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->limit(5)
            ->get();

        return [
            'total' => $this->countModel(Menu::class),
            'active' => $this->countModel(Menu::class, fn ($query) => $query->where('is_active', true)),
            'items' => (int) Menu::query()->withCount('items')->get()->sum('items_count'),
            'menus' => $menus->map(fn (Menu $menu): array => [
                'name' => $menu->name,
                'location' => $menu->location,
                'items_count' => $menu->items_count,
                'is_active' => $menu->is_active,
            ])->all(),
        ];
    }

    private function homepageSectionSummary(): array
    {
        $homepage = $this->firstModel(Page::class, fn ($query) => $query->where('slug', 'home'));

        if (! $homepage) {
            return ['page' => null, 'sections' => []];
        }

        return [
            'page' => [
                'title' => $homepage->title,
                'status' => $homepage->status,
            ],
            'sections' => $homepage->sections()
                ->with('sectionType')
                ->orderBy('sort_order')
                ->get()
                ->map(fn (PageSection $section): array => [
                    'title' => $section->title ?: $section->sectionType?->name,
                    'type' => $section->sectionType?->name,
                    'is_active' => $section->is_active,
                    'sort_order' => $section->sort_order,
                ])
                ->all(),
        ];
    }

    private function supportedHomepageSections(): array
    {
        if (! $this->modelTableExists(SectionType::class)) {
            return [];
        }

        return SectionType::query()
            ->where('code', 'hero')
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->map(fn (SectionType $sectionType): array => [
                'name' => $sectionType->name,
                'code' => $sectionType->code,
                'category' => $sectionType->category,
            ])
            ->all();
    }

    private function siteSettingsSummary(): array
    {
        if (! $this->modelTableExists(SiteSetting::class)) {
            return ['total' => 0, 'groups' => []];
        }

        return [
            'total' => $this->countModel(SiteSetting::class),
            'groups' => SiteSetting::query()
                ->orderBy('group')
                ->get(['group'])
                ->groupBy(fn (SiteSetting $setting): string => $setting->group ?: 'Ungrouped')
                ->map(fn ($settings, string $group): array => [
                    'name' => $group,
                    'count' => $settings->count(),
                ])
                ->values()
                ->all(),
        ];
    }

    private function siteSettingsGroups(): array
    {
        $definitions = [
            [
                'name' => 'Store Identity',
                'description' => 'Brand name, logo, favicon, and storefront identity defaults.',
                'keys' => ['store.identity'],
            ],
            [
                'name' => 'Contact',
                'description' => 'Public contact details used by storefront theme surfaces.',
                'keys' => ['store.contact'],
            ],
            [
                'name' => 'Social Links',
                'description' => 'Social profile links used by footer and website sections.',
                'keys' => ['store.social_links'],
            ],
            [
                'name' => 'Trust / Footer Info',
                'description' => 'Payment, delivery, return, and footer trust messaging defaults.',
                'keys' => ['store.trust', 'store.footer'],
            ],
            [
                'name' => 'SEO Defaults',
                'description' => 'Default metadata used when page-level SEO is not configured.',
                'keys' => ['seo.defaults'],
            ],
        ];

        if (! $this->modelTableExists(SiteSetting::class)) {
            return collect($definitions)
                ->map(fn (array $definition): array => [
                    ...$definition,
                    'status' => 'Missing',
                    'configured_fields' => 0,
                    'updated_at' => null,
                ])
                ->all();
        }

        $settings = SiteSetting::query()
            ->whereIn('key', collect($definitions)->flatMap(fn (array $definition): array => $definition['keys'])->all())
            ->get()
            ->keyBy('key');

        return collect($definitions)
            ->map(function (array $definition) use ($settings): array {
                $records = collect($definition['keys'])
                    ->map(fn (string $key) => $settings->get($key))
                    ->filter();

                return [
                    ...$definition,
                    'status' => $records->isNotEmpty() ? 'Configured' : 'Missing',
                    'configured_fields' => $records->sum(fn (SiteSetting $setting): int => collect($setting->value_json ?? [])->filter(fn (mixed $value): bool => filled($value))->count()),
                    'updated_at' => $records->max(fn (SiteSetting $setting) => $setting->updated_at),
                ];
            })
            ->all();
    }

    private function siteSettingValue(string $key, ?string $path = null): mixed
    {
        if (! $this->modelTableExists(SiteSetting::class)) {
            return null;
        }

        $value = SiteSetting::query()
            ->where('key', $key)
            ->value('value_json');

        if (! is_array($value)) {
            return null;
        }

        return $path ? data_get($value, $path) : $value;
    }

    private function countModel(string $modelClass, ?callable $queryCallback = null): int
    {
        if (! $this->modelTableExists($modelClass)) {
            return 0;
        }

        $query = $modelClass::query();

        if ($queryCallback) {
            $queryCallback($query);
        }

        return $query->count();
    }

    private function firstModel(string $modelClass, ?callable $queryCallback = null): ?Model
    {
        if (! $this->modelTableExists($modelClass)) {
            return null;
        }

        $query = $modelClass::query();

        if ($queryCallback) {
            $queryCallback($query);
        }

        return $query->first();
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
