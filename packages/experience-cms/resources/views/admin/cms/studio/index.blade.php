@php
    $pageTitleClass = 'font-sans text-2xl leading-8 font-bold tracking-tight text-slate-950 dark:text-white';
    $pageSubtitleClass = 'mt-1 text-sm leading-6 text-slate-500 dark:text-gray-400';
    $navClass = 'rounded-[24px] border border-slate-200/70 bg-white p-4 shadow-none dark:border-gray-700 dark:bg-gray-800';
    $panelClass = 'rounded-[24px] border border-slate-200/70 bg-white p-6 shadow-none dark:border-gray-700 dark:bg-gray-800';
    $panelTitleClass = 'font-sans text-lg leading-7 font-semibold tracking-normal text-slate-950 dark:text-white';
    $bodyTextClass = 'text-sm leading-6 text-slate-500 dark:text-gray-400';
    $labelClass = 'mb-2 block text-sm font-medium text-slate-700 dark:text-gray-300';
    $inputClass = 'w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-800 outline-none transition focus:border-blue-300 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-700 dark:focus:ring-blue-950';
    $headerSectionClass = 'rounded-[20px] border border-slate-200/70 bg-white p-5 shadow-none dark:border-gray-700 dark:bg-gray-800';
    $headerSectionTitleClass = 'font-sans text-base font-semibold text-slate-950 dark:text-white';
    $headerSectionDescriptionClass = 'mt-1 text-sm leading-6 text-slate-500 dark:text-gray-400';
    $headerLabelClass = 'mb-2 block text-sm font-medium text-slate-700 dark:text-gray-300';
    $headerInputClass = 'w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-blue-300 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 dark:focus:border-blue-700 dark:focus:ring-blue-950';
    $headerHelperClass = 'mt-1 text-xs leading-5 text-slate-500 dark:text-gray-400';
    $headerToggleRowClass = 'flex items-center justify-between gap-4 rounded-xl border border-slate-200/70 bg-slate-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-700/60';
    $toggleClass = 'h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950';
    $secondaryButtonClass = 'inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-blue-700 dark:hover:bg-blue-950/40 dark:hover:text-blue-300';
    $primaryButtonClass = 'inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700';
    $navChipBaseClass = 'flex items-center justify-between rounded-xl px-3 py-2 text-sm font-medium transition';
    $navChipActiveClass = 'bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-300';
    $navChipInactiveClass = 'text-slate-600 hover:bg-slate-100 hover:text-blue-600 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-blue-300';
    $values = $editor['values'] ?? [];
@endphp

<x-admin::layouts>
    <x-slot:title>CMS Studio</x-slot:title>

    @pushOnce('styles')
        <style>
            .cms-studio-area-nav {
                scrollbar-width: thin;
            }

            .cms-studio-shell {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: 1.5rem;
            }

            .cms-studio-preview-viewport {
                width: 100%;
                margin-left: auto;
                margin-right: auto;
                transition: max-width 180ms ease-out;
            }

            .cms-studio-preview-viewport.cms-preview-desktop {
                max-width: 100%;
            }

            .cms-studio-preview-viewport.cms-preview-tablet {
                max-width: 768px;
            }

            .cms-studio-preview-viewport.cms-preview-mobile {
                max-width: 390px;
            }

            .cms-preview-mobile .cms-preview-desktop-only {
                display: none;
            }

            .cms-preview-mobile .cms-preview-mobile-only {
                display: flex;
            }

            .cms-preview-desktop .cms-preview-mobile-only,
            .cms-preview-tablet .cms-preview-mobile-only {
                display: none;
            }

            .cms-header-preview-main {
                display: grid;
                grid-template-columns: minmax(0, auto) minmax(0, 1fr) minmax(0, auto);
                align-items: center;
            }

            .cms-header-preview-main[data-header-variant="centered"] {
                grid-template-columns: minmax(0, 1fr) minmax(0, auto) minmax(0, 1fr);
            }

            .cms-header-preview-main[data-header-variant="centered"] [data-header-nav] {
                justify-content: flex-start;
                order: 1;
            }

            .cms-header-preview-main[data-header-variant="centered"] [data-header-brand] {
                justify-content: center;
                order: 2;
            }

            .cms-header-preview-main[data-header-variant="centered"] [data-header-actions] {
                justify-content: flex-end;
                order: 3;
            }

            .cms-header-preview-main[data-header-variant="minimal"] {
                grid-template-columns: minmax(0, 1fr) minmax(0, auto);
            }

            .cms-header-preview-main[data-header-variant="minimal"] [data-header-nav] {
                display: none;
            }

            .cms-header-preview-main[data-header-variant="minimal"] [data-header-brand] {
                order: 1;
            }

            .cms-header-preview-main[data-header-variant="minimal"] [data-header-actions] {
                order: 2;
            }

            .cms-preview-mobile .cms-header-preview-main {
                display: flex;
            }

            .cms-header-variant-card:has(input:checked) {
                border-color: rgb(147 197 253);
                background: rgb(239 246 255);
            }

            .dark .cms-header-variant-card:has(input:checked) {
                border-color: rgb(30 64 175);
                background: rgb(23 37 84 / 0.3);
            }
        </style>
    @endPushOnce

    <div class="space-y-6 pb-8">
        <section class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="{{ $pageTitleClass }}">
                    CMS Studio
                </h1>

                <p class="{{ $pageSubtitleClass }}">
                    Edit website header, footer, navigation, homepage content, reusable blocks, and site settings.
                </p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row">
                <a
                    href="{{ $previewStorefrontUrl }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="{{ $secondaryButtonClass }}"
                >
                    Preview Storefront
                </a>

                @if ($canSave)
                    <button
                        type="submit"
                        form="cms-studio-form"
                        class="{{ $primaryButtonClass }}"
                    >
                        {{ $editor['save_label'] ?? 'Save Draft' }}
                    </button>
                @endif
            </div>
        </section>

        <section class="cms-studio-shell">
            <aside class="{{ $navClass }}">
                <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="font-sans text-base font-semibold text-slate-950 dark:text-white">
                        My Website / CMS Studio
                    </h2>

                    <p class="text-xs font-medium text-slate-400 dark:text-gray-500">
                        Safe website areas only
                    </p>
                </div>

                <div class="cms-studio-area-nav overflow-x-auto pb-1">
                @foreach ($navigationGroups as $group)
                    <div>
                        <div class="flex min-w-max flex-nowrap gap-2 sm:min-w-0 sm:flex-wrap">
                            @foreach ($group['items'] as $item)
                                <a
                                    href="{{ $item['url'] }}"
                                    class="{{ $navChipBaseClass }} {{ $item['active'] ? $navChipActiveClass : $navChipInactiveClass }}"
                                >
                                    <span>{{ $item['label'] }}</span>

                                    @if ($item['active'])
                                        <span class="h-1.5 w-1.5 rounded-full bg-blue-600 dark:bg-blue-300"></span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                </div>
            </aside>

            <section class="{{ $panelClass }} min-w-0">
            <div class="mb-6">
                <h2 class="{{ $panelTitleClass }}">
                    {{ $editor['title'] }}
                </h2>

                <p class="mt-1 {{ $bodyTextClass }}">
                    {{ $editor['description'] }}
                </p>
            </div>

            @if (! empty($editor['storage_error']))
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300">
                    {{ $editor['storage_error'] }}
                </div>
            @elseif ($editor['type'] === 'header')
                <form
                    id="cms-studio-form"
                    method="POST"
                    enctype="multipart/form-data"
                    action="{{ $editor['form_action'] }}"
                    class="space-y-6"
                >
                    @csrf
                    <input type="hidden" name="name" value="{{ old('name', $values['name'] ?? 'Default Header') }}">
                    <input type="hidden" name="variant" value="{{ old('variant', $values['variant'] ?? 'classic') }}">
                    <input type="hidden" name="logo_url" value="{{ old('logo_url', $values['logo_url'] ?? '') }}" data-preview-input="logo_url" data-logo-url-input>

                    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                    <div class="{{ $headerSectionClass }}">
                        <h3 class="{{ $headerSectionTitleClass }}">
                            Logo
                        </h3>

                        <p class="{{ $headerSectionDescriptionClass }}">
                            Upload the logo used by the active storefront header.
                        </p>

                        <div class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-[220px_minmax(0,1fr)] xl:grid-cols-1 2xl:grid-cols-[220px_minmax(0,1fr)]">
                            <div class="flex min-h-[128px] items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-5 dark:border-gray-700 dark:bg-gray-950">
                                <img
                                    src="{{ old('logo_url', $values['logo_url'] ?? '') }}"
                                    alt=""
                                    class="{{ old('logo_url', $values['logo_url'] ?? '') ? '' : 'hidden' }} max-h-20 max-w-full object-contain"
                                    data-logo-upload-preview
                                    onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');"
                                >

                                <span class="{{ old('logo_url', $values['logo_url'] ?? '') ? 'hidden' : '' }} text-center text-sm font-medium text-slate-500 dark:text-gray-400" data-logo-upload-empty>
                                    No logo uploaded
                                </span>
                            </div>

                            <label class="block">
                                <span class="{{ $headerLabelClass }}">Upload logo image</span>
                                <input
                                    type="file"
                                    name="logo_file"
                                    accept="image/*"
                                    class="{{ $headerInputClass }} file:mr-4 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-950/40 dark:file:text-blue-300"
                                    data-logo-file-input
                                >
                                <span class="{{ $headerHelperClass }}">PNG, JPG, SVG, GIF, or WebP up to 2 MB. Uploading a new logo replaces the current logo.</span>
                                @error('logo_file')
                                    <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                                @enderror
                                @error('logo_url')
                                    <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                                @enderror
                            </label>
                        </div>
                    </div>

                    <div class="{{ $headerSectionClass }}">
                        <h3 class="{{ $headerSectionTitleClass }}">
                            Announcement Bar
                        </h3>

                        <p class="{{ $headerSectionDescriptionClass }}">
                            Show a short promotional or service message above the main header.
                        </p>

                        <input type="hidden" name="announcement_enabled" value="0">

                        <label class="mt-5 {{ $headerToggleRowClass }}">
                            <span>
                                <span class="block text-sm font-medium text-slate-700 dark:text-gray-300">Announcement enabled</span>
                                <span class="{{ $headerHelperClass }}">Display the message bar above the main header.</span>
                            </span>

                            <input
                                type="checkbox"
                                name="announcement_enabled"
                                value="1"
                                class="{{ $toggleClass }} shrink-0"
                                data-preview-input="announcement_enabled"
                                @checked(old('announcement_enabled', $values['announcement_enabled'] ?? false))
                            >
                        </label>

                        <div
                            class="mt-5 grid grid-cols-1 gap-5 transition md:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2 {{ old('announcement_enabled', $values['announcement_enabled'] ?? false) ? '' : 'opacity-60' }}"
                            data-announcement-fields
                        >
                            <label class="block">
                                <span class="{{ $headerLabelClass }}">Announcement text</span>
                                <input name="announcement_text" value="{{ old('announcement_text', $values['announcement_text'] ?? '') }}" class="{{ $headerInputClass }}" data-preview-input="announcement_text">
                                @error('announcement_text')
                                    <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                                @enderror
                            </label>

                            <label class="block">
                                <span class="{{ $headerLabelClass }}">Announcement link</span>
                                <input name="announcement_link" value="{{ old('announcement_link', $values['announcement_link'] ?? '') }}" class="{{ $headerInputClass }}">
                                @error('announcement_link')
                                    <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                                @enderror
                            </label>
                        </div>
                    </div>

                    <div class="{{ $headerSectionClass }}">
                        <h3 class="{{ $headerSectionTitleClass }}">
                            Navigation
                        </h3>

                        <p class="{{ $headerSectionDescriptionClass }}">
                            Select the menu used in the main storefront header.
                        </p>

                        @if (! empty($editor['menus']))
                            <label class="mt-5 block">
                                <span class="{{ $headerLabelClass }}">Navigation menu selector</span>
                                <select name="menu_id" class="{{ $headerInputClass }}" data-preview-input="menu_id">
                                    <option value="">No menu selected</option>

                                    @foreach ($editor['menus'] as $menu)
                                        <option
                                            value="{{ $menu['id'] }}"
                                            data-items="{{ base64_encode(json_encode($menu['items'])) }}"
                                            @selected((string) old('menu_id', $values['menu_id'] ?? '') === (string) $menu['id'])
                                        >
                                            {{ $menu['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('menu_id')
                                    <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                                @enderror
                            </label>
                        @else
                            <p class="mt-5 rounded-xl border border-slate-200/70 bg-slate-50 px-4 py-3 text-sm text-slate-500 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-400">
                                No navigation menus available yet.
                            </p>
                        @endif
                    </div>

                    <div class="{{ $headerSectionClass }}">
                        <h3 class="{{ $headerSectionTitleClass }}">
                            Header Features
                        </h3>

                        <p class="{{ $headerSectionDescriptionClass }}">
                            Control common storefront header features.
                        </p>

                        <div class="mt-5 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2">
                            @foreach ([
                                'show_search' => ['label' => 'Show search', 'description' => 'Display storefront search in the header.'],
                                'show_account' => ['label' => 'Show account icon', 'description' => 'Display customer account access in the header.'],
                                'show_cart' => ['label' => 'Show cart icon', 'description' => 'Display cart access in the header.'],
                                'sticky' => ['label' => 'Sticky header', 'description' => 'Keep the header visible while scrolling.'],
                            ] as $field => $feature)
                                <input type="hidden" name="{{ $field }}" value="0">

                                <label class="{{ $headerToggleRowClass }}">
                                    <span>
                                        <span class="block text-sm font-medium text-slate-700 dark:text-gray-300">{{ $feature['label'] }}</span>
                                        <span class="{{ $headerHelperClass }}">{{ $feature['description'] }}</span>
                                    </span>

                                    <input
                                        type="checkbox"
                                        name="{{ $field }}"
                                        value="1"
                                        class="{{ $toggleClass }} shrink-0"
                                        data-preview-input="{{ $field }}"
                                        @checked(old($field, $values[$field] ?? false))
                                    >
                                </label>
                            @endforeach
                        </div>
                    </div>
                    </div>

                </form>
            @elseif ($editor['type'] === 'navigation')
                @php
                    $navigationValues = [
                        'id' => old('menu_id', $values['id'] ?? null),
                        'name' => old('name', $values['name'] ?? ''),
                        'location' => old('location', $values['location'] ?? 'header'),
                        'is_active' => old('is_active', $values['is_active'] ?? true),
                    ];
                    $navigationItems = old('items', $values['items'] ?? []);

                    if (empty($navigationItems)) {
                        $navigationItems = [[
                            'title' => '',
                            'type' => 'url',
                            'target' => '',
                            'sort_order' => 1,
                            'is_active' => true,
                            'open_in_new_tab' => false,
                        ]];
                    }
                @endphp

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-[280px_minmax(0,1fr)]">
                    <aside class="{{ $headerSectionClass }} h-fit">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="{{ $headerSectionTitleClass }}">
                                Menus
                            </h3>

                            <a
                                href="{{ $editor['create_url'] }}"
                                class="rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-100 dark:bg-blue-950/40 dark:text-blue-300 dark:hover:bg-blue-950"
                            >
                                New
                            </a>
                        </div>

                        @if (! empty($editor['menus']))
                            <div class="mt-4 space-y-2">
                                @foreach ($editor['menus'] as $menu)
                                    <a
                                        href="{{ $menu['edit_url'] }}"
                                        class="block rounded-xl border px-4 py-3 transition {{ (string) $navigationValues['id'] === (string) $menu['id'] ? 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900 dark:bg-blue-950/30 dark:text-blue-300' : 'border-slate-200/70 text-slate-700 hover:border-blue-200 hover:bg-blue-50 dark:border-gray-700 dark:text-gray-300 dark:hover:border-blue-900 dark:hover:bg-blue-950/30' }}"
                                    >
                                        <span class="block text-sm font-semibold">{{ $menu['name'] }}</span>
                                        <span class="mt-1 flex items-center justify-between gap-3 text-xs text-slate-500 dark:text-gray-400">
                                            <span>{{ $menu['location_label'] }}</span>
                                            <span>{{ $menu['items_count'] }} items</span>
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <p class="mt-4 rounded-xl bg-slate-50 px-4 py-3 text-sm leading-6 text-slate-500 dark:bg-gray-950 dark:text-gray-400">
                                No menus have been created yet.
                            </p>
                        @endif
                    </aside>

                    <form
                        id="cms-studio-form"
                        method="POST"
                        action="{{ $editor['form_action'] }}"
                        class="min-w-0 space-y-6"
                    >
                        @csrf

                        <input type="hidden" name="menu_id" value="{{ $navigationValues['id'] }}">

                        <div class="{{ $headerSectionClass }}">
                            <h3 class="{{ $headerSectionTitleClass }}">
                                Menu Settings
                            </h3>

                            <p class="{{ $headerSectionDescriptionClass }}">
                                Create or edit a flat storefront menu used by header, footer, mobile, or utility navigation.
                            </p>

                            <div class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-2">
                                <label class="block">
                                    <span class="{{ $headerLabelClass }}">Menu name</span>
                                    <input name="name" value="{{ $navigationValues['name'] }}" class="{{ $headerInputClass }}">
                                    @error('name')
                                        <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                                    @enderror
                                </label>

                                <label class="block">
                                    <span class="{{ $headerLabelClass }}">Usage context</span>
                                    <select name="location" class="{{ $headerInputClass }}">
                                        @foreach ($editor['locations'] as $location => $label)
                                            <option value="{{ $location }}" @selected($navigationValues['location'] === $location)>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('location')
                                        <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                                    @enderror
                                </label>
                            </div>

                            <input type="hidden" name="is_active" value="0">

                            <label class="mt-5 {{ $headerToggleRowClass }}">
                                <span>
                                    <span class="block text-sm font-medium text-slate-700 dark:text-gray-300">Active menu</span>
                                    <span class="{{ $headerHelperClass }}">Only active menus are available for storefront selection.</span>
                                </span>

                                <input
                                    type="checkbox"
                                    name="is_active"
                                    value="1"
                                    class="{{ $toggleClass }} shrink-0"
                                    @checked((bool) $navigationValues['is_active'])
                                >
                            </label>
                        </div>

                        <div class="{{ $headerSectionClass }}" data-navigation-builder>
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <h3 class="{{ $headerSectionTitleClass }}">
                                        Menu Items
                                    </h3>

                                    <p class="{{ $headerSectionDescriptionClass }}">
                                        Add flat menu links, set their order, and disable links without deleting them.
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    class="{{ $secondaryButtonClass }}"
                                    data-add-menu-item
                                >
                                    Add item
                                </button>
                            </div>

                            @error('items')
                                <span class="mt-3 block text-sm text-red-600">{{ $message }}</span>
                            @enderror

                            <div class="mt-5 space-y-4" data-menu-items>
                                @foreach ($navigationItems as $index => $item)
                                    <div class="rounded-2xl border border-slate-200/70 bg-slate-50 p-4 dark:border-gray-700 dark:bg-gray-950" data-menu-item-row>
                                        <div class="mb-4 flex items-center justify-between gap-3">
                                            <p class="text-sm font-semibold text-slate-800 dark:text-gray-100">
                                                Item {{ $loop->iteration }}
                                            </p>

                                            <div class="flex flex-wrap items-center gap-4">
                                                <input type="hidden" name="items[{{ $index }}][is_active]" value="0">
                                                <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-500 dark:text-gray-400">
                                                    <input
                                                        type="checkbox"
                                                        name="items[{{ $index }}][is_active]"
                                                        value="1"
                                                        class="{{ $toggleClass }}"
                                                        @checked((bool) ($item['is_active'] ?? false))
                                                    >
                                                    Active
                                                </label>

                                                <input type="hidden" name="items[{{ $index }}][open_in_new_tab]" value="0">
                                                <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-500 dark:text-gray-400">
                                                    <input
                                                        type="checkbox"
                                                        name="items[{{ $index }}][open_in_new_tab]"
                                                        value="1"
                                                        class="{{ $toggleClass }}"
                                                        @checked((bool) ($item['open_in_new_tab'] ?? false))
                                                    >
                                                    New tab
                                                </label>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1.1fr)_160px_minmax(0,1.3fr)_100px]">
                                            <label class="block">
                                                <span class="{{ $headerLabelClass }}">Label</span>
                                                <input name="items[{{ $index }}][title]" value="{{ $item['title'] ?? '' }}" class="{{ $headerInputClass }}">
                                            </label>

                                            <label class="block">
                                                <span class="{{ $headerLabelClass }}">Type</span>
                                                <select name="items[{{ $index }}][type]" class="{{ $headerInputClass }}">
                                                    @foreach ($editor['item_types'] as $type => $label)
                                                        <option value="{{ $type }}" @selected(($item['type'] ?? 'url') === $type)>
                                                            {{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </label>

                                            <label class="block">
                                                <span class="{{ $headerLabelClass }}">URL or path</span>
                                                <input name="items[{{ $index }}][target]" value="{{ $item['target'] ?? '' }}" class="{{ $headerInputClass }}">
                                            </label>

                                            <label class="block">
                                                <span class="{{ $headerLabelClass }}">Order</span>
                                                <input type="number" min="0" name="items[{{ $index }}][sort_order]" value="{{ $item['sort_order'] ?? $loop->iteration }}" class="{{ $headerInputClass }}">
                                            </label>
                                        </div>

                                        @error("items.$index.title")
                                            <span class="mt-2 block text-sm text-red-600">{{ $message }}</span>
                                        @enderror

                                        @error("items.$index.target")
                                            <span class="mt-2 block text-sm text-red-600">{{ $message }}</span>
                                        @enderror
                                    </div>
                                @endforeach
                            </div>

                            <template data-menu-item-template>
                                <div class="rounded-2xl border border-slate-200/70 bg-slate-50 p-4 dark:border-gray-700 dark:bg-gray-950" data-menu-item-row>
                                    <div class="mb-4 flex items-center justify-between gap-3">
                                        <p class="text-sm font-semibold text-slate-800 dark:text-gray-100">
                                            Item __NUMBER__
                                        </p>

                                        <div class="flex flex-wrap items-center gap-4">
                                            <input type="hidden" name="items[__INDEX__][is_active]" value="0">
                                            <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-500 dark:text-gray-400">
                                                <input type="checkbox" name="items[__INDEX__][is_active]" value="1" class="{{ $toggleClass }}" checked>
                                                Active
                                            </label>

                                            <input type="hidden" name="items[__INDEX__][open_in_new_tab]" value="0">
                                            <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-500 dark:text-gray-400">
                                                <input type="checkbox" name="items[__INDEX__][open_in_new_tab]" value="1" class="{{ $toggleClass }}">
                                                New tab
                                            </label>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1.1fr)_160px_minmax(0,1.3fr)_100px]">
                                        <label class="block">
                                            <span class="{{ $headerLabelClass }}">Label</span>
                                            <input name="items[__INDEX__][title]" class="{{ $headerInputClass }}">
                                        </label>

                                        <label class="block">
                                            <span class="{{ $headerLabelClass }}">Type</span>
                                            <select name="items[__INDEX__][type]" class="{{ $headerInputClass }}">
                                                @foreach ($editor['item_types'] as $type => $label)
                                                    <option value="{{ $type }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </label>

                                        <label class="block">
                                            <span class="{{ $headerLabelClass }}">URL or path</span>
                                            <input name="items[__INDEX__][target]" class="{{ $headerInputClass }}">
                                        </label>

                                        <label class="block">
                                            <span class="{{ $headerLabelClass }}">Order</span>
                                            <input type="number" min="0" name="items[__INDEX__][sort_order]" value="__NUMBER__" class="{{ $headerInputClass }}">
                                        </label>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </form>
                </div>
            @elseif ($editor['type'] === 'homepage')
                @php
                    $homepageHasSectionErrors = collect($errors->getMessages())
                        ->keys()
                        ->contains(fn ($key) => \Illuminate\Support\Str::startsWith((string) $key, 'sections'));
                    $homepageSections = collect($homepageHasSectionErrors ? old('sections', $values['sections'] ?? []) : ($values['sections'] ?? []))
                        ->filter(fn ($section) => ($section['section_code'] ?? null) === 'hero')
                        ->take(1)
                        ->values()
                        ->all();
                    $hasHomepageHero = ! empty($homepageSections);
                    $addHeroButtonClass = $hasHomepageHero
                        ? 'inline-flex cursor-not-allowed items-center justify-center rounded-xl border border-slate-200 bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-500'
                        : $secondaryButtonClass;

                    $emptyHomepageSettings = [
                        'mode' => 'static',
                        'slides' => [],
                    ];
                @endphp

                <form
                    id="cms-studio-form"
                    method="POST"
                    enctype="multipart/form-data"
                    action="{{ $editor['form_action'] }}"
                    class="space-y-6"
                    data-homepage-builder
                >
                    @csrf

                    <div class="{{ $headerSectionClass }}">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <h3 class="{{ $headerSectionTitleClass }}">
                                    Homepage Hero
                                </h3>

                                <p class="{{ $headerSectionDescriptionClass }}">
                                    Configure one theme-neutral Hero. The active storefront theme controls how it is rendered.
                                </p>
                            </div>

                            <button
                                type="button"
                                class="{{ $addHeroButtonClass }}"
                                data-add-homepage-section
                                onclick="window.cmsHomepageHeroAdd?.(this)"
                                @disabled($hasHomepageHero)
                            >
                                {{ $hasHomepageHero ? 'Hero already added' : 'Add Hero' }}
                            </button>
                        </div>

                        @error('sections')
                            <span class="mt-3 block text-sm text-red-600">{{ $message }}</span>
                        @enderror

                        <div class="mt-5 grid grid-cols-1 gap-4" data-homepage-sections>
                            @forelse ($homepageSections as $index => $section)
                                @php
                                    $sectionSettings = array_replace($emptyHomepageSettings, $section['settings'] ?? []);
                                    $heroMode = in_array($sectionSettings['mode'] ?? 'static', ['static', 'slider'], true) ? $sectionSettings['mode'] : 'static';
                                @endphp

                                <div class="rounded-2xl border border-slate-200/70 bg-slate-50 p-4 dark:border-gray-700 dark:bg-gray-950" data-homepage-section-row data-homepage-active-slide="0">
                                    <input type="hidden" name="sections[{{ $index }}][id]" value="{{ $section['id'] ?? '' }}">
                                    <input type="hidden" name="sections[{{ $index }}][section_code]" value="hero" data-homepage-section-code>

                                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-semibold text-slate-800 dark:text-gray-100">
                                                Hero
                                            </p>

                                            <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">
                                                Static image or auto slider for the homepage opening area.
                                            </p>
                                        </div>

                                        <div class="flex flex-wrap items-center gap-4">
                                            <input type="hidden" name="sections[{{ $index }}][is_active]" value="0">
                                            <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-500 dark:text-gray-400">
                                                <input
                                                    type="checkbox"
                                                    name="sections[{{ $index }}][is_active]"
                                                    value="1"
                                                    class="{{ $toggleClass }}"
                                                    @checked((bool) ($section['is_active'] ?? false))
                                                >
                                                Enabled
                                            </label>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1fr)_140px]">
                                        <label class="block">
                                            <span class="{{ $headerLabelClass }}">Section title</span>
                                            <input name="sections[{{ $index }}][title]" value="{{ $section['title'] ?? '' }}" class="{{ $headerInputClass }}" data-homepage-section-title>
                                        </label>

                                        <label class="block">
                                            <span class="{{ $headerLabelClass }}">Order</span>
                                            <input type="number" min="0" name="sections[{{ $index }}][sort_order]" value="{{ $section['sort_order'] ?? $loop->iteration }}" class="{{ $headerInputClass }}">
                                        </label>
                                    </div>

                                    @error("sections.$index.section_code")
                                        <span class="mt-2 block text-sm text-red-600">{{ $message }}</span>
                                    @enderror

                                    <div class="mt-5" data-homepage-fields="hero">
                                        <input type="hidden" name="sections[{{ $index }}][settings][mode]" value="{{ $heroMode }}" data-homepage-hero-mode-value>

                                        <div class="mb-4 flex flex-wrap items-center gap-2 rounded-2xl border border-slate-200 bg-white p-2 dark:border-gray-700 dark:bg-gray-800">
                                            <label class="cursor-pointer">
                                                <input type="radio" name="sections[{{ $index }}][settings][mode_toggle]" value="static" class="peer sr-only" data-homepage-hero-mode onchange="window.cmsHomepageHeroModeChanged?.(this)" @checked($heroMode === 'static')>
                                                <span class="inline-flex rounded-xl px-4 py-2 text-sm font-medium text-slate-500 transition peer-checked:bg-blue-50 peer-checked:text-blue-600 dark:text-gray-400 dark:peer-checked:bg-blue-950/40 dark:peer-checked:text-blue-300">
                                                    Static image
                                                </span>
                                            </label>

                                            <label class="cursor-pointer">
                                                <input type="radio" name="sections[{{ $index }}][settings][mode_toggle]" value="slider" class="peer sr-only" data-homepage-hero-mode onchange="window.cmsHomepageHeroModeChanged?.(this)" @checked($heroMode === 'slider')>
                                                <span class="inline-flex rounded-xl px-4 py-2 text-sm font-medium text-slate-500 transition peer-checked:bg-blue-50 peer-checked:text-blue-600 dark:text-gray-400 dark:peer-checked:bg-blue-950/40 dark:peer-checked:text-blue-300">
                                                    Auto slider
                                                </span>
                                            </label>
                                        </div>

                                        <div class="{{ $heroMode === 'slider' ? '' : 'hidden' }} mb-4 rounded-2xl border border-slate-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800" data-homepage-hero-slide-tabs>
                                            <div class="flex flex-wrap items-center gap-2">
                                                @for ($slideIndex = 0; $slideIndex < 5; $slideIndex++)
                                                    @php
                                                        $slide = $sectionSettings['slides'][$slideIndex] ?? [];
                                                        $slideImage = (string) ($slide['image'] ?? '');
                                                        $slideImageUrl = $slideImage !== '' ? (\Illuminate\Support\Str::startsWith($slideImage, ['http://', 'https://', '/']) ? $slideImage : asset($slideImage)) : null;
                                                        $slideHasContent = collect($slide)->filter(fn ($value) => filled($value))->isNotEmpty();
                                                        $slideEnabled = $slideIndex === 0 || ($heroMode === 'slider' && $slideHasContent);
                                                    @endphp

                                                    <button
                                                        type="button"
                                                        class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-xs font-semibold transition {{ $slideIndex === 0 ? 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-300' : 'border-slate-200 bg-slate-50 text-slate-500 hover:border-blue-200 hover:text-blue-600 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-400 dark:hover:border-blue-900 dark:hover:text-blue-300' }} {{ $heroMode !== 'slider' ? 'hidden' : '' }}"
                                                        data-homepage-hero-slide-tab
                                                        data-slide-index="{{ $slideIndex }}"
                                                        onclick="window.cmsHomepageHeroSelectSlide?.(this)"
                                                    >
                                                        @if ($slideImageUrl)
                                                            <img src="{{ $slideImageUrl }}" alt="" class="h-8 w-10 rounded-lg object-cover">
                                                        @else
                                                            <span class="flex h-8 w-10 items-center justify-center rounded-lg bg-white text-xs text-slate-400 dark:bg-gray-900 dark:text-gray-500">
                                                                {{ $slideIndex + 1 }}
                                                            </span>
                                                        @endif
                                                        Slide {{ $slideIndex + 1 }}
                                                    </button>
                                                @endfor
                                            </div>
                                        </div>

                                        <div>
                                            @for ($slideIndex = 0; $slideIndex < 5; $slideIndex++)
                                                @php
                                                    $slide = $sectionSettings['slides'][$slideIndex] ?? [];
                                                    $slideImage = (string) ($slide['image'] ?? '');
                                                    $slideImageUrl = $slideImage !== '' ? (\Illuminate\Support\Str::startsWith($slideImage, ['http://', 'https://', '/']) ? $slideImage : asset($slideImage)) : null;
                                                    $slideHasContent = collect($slide)->filter(fn ($value) => filled($value))->isNotEmpty();
                                                    $slideEnabled = $slideIndex === 0 || ($heroMode === 'slider' && $slideHasContent);
                                                    $slidePanelVisible = $slideIndex === 0;
                                                @endphp

                                                <div
                                                    class="rounded-2xl border border-slate-200/70 bg-white p-4 dark:border-gray-700 dark:bg-gray-800 {{ ! $slidePanelVisible ? 'hidden' : '' }}"
                                                    data-homepage-hero-slide-panel
                                                    data-slide-index="{{ $slideIndex }}"
                                                >
                                                    <input type="hidden" name="sections[{{ $index }}][settings][slides][{{ $slideIndex }}][enabled]" value="{{ $slideEnabled ? 1 : 0 }}" data-homepage-hero-slide-enabled>

                                                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                                                        <div>
                                                            <p class="text-sm font-semibold text-slate-800 dark:text-gray-100">
                                                                {{ $slideIndex === 0 ? 'Primary hero slide' : 'Slide '.($slideIndex + 1) }}
                                                            </p>

                                                            <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">
                                                                {{ $slideIndex === 0 ? 'Used by static mode and as the first slider frame.' : 'Only used when Auto slider is selected.' }}
                                                            </p>
                                                        </div>

                                                        <button
                                                            type="button"
                                                            class="{{ $slideIndex === 0 ? 'hidden' : '' }} text-xs font-semibold text-red-500 transition hover:text-red-600"
                                                            data-homepage-remove-hero-slide
                                                            onclick="window.cmsHomepageHeroRemoveSlide?.(this)"
                                                        >
                                                            Remove
                                                        </button>
                                                    </div>

                                                    @if ($slideImageUrl)
                                                        <img
                                                            src="{{ $slideImageUrl }}"
                                                            alt="{{ $slide['title'] ?? '' }}"
                                                            class="mb-4 h-40 w-full rounded-xl border border-slate-200 object-cover dark:border-gray-700"
                                                        >
                                                    @endif

                                                    <input type="hidden" name="sections[{{ $index }}][settings][slides][{{ $slideIndex }}][current_image]" value="{{ $slideImage }}" data-homepage-hero-current-image>

                                                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                                                        <label class="block">
                                                            <span class="{{ $headerLabelClass }}">Image</span>
                                                            <input type="file" name="sections[{{ $index }}][settings][slides][{{ $slideIndex }}][image_file]" accept="image/png,image/jpeg,image/jpg,image/webp" class="{{ $headerInputClass }}">
                                                            @error("sections.$index.settings.slides.$slideIndex.image_file")
                                                                <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                                                            @enderror
                                                        </label>

                                                        <label class="block">
                                                            <span class="{{ $headerLabelClass }}">Alt / title</span>
                                                            <input name="sections[{{ $index }}][settings][slides][{{ $slideIndex }}][title]" value="{{ $slide['title'] ?? '' }}" class="{{ $headerInputClass }}">
                                                        </label>

                                                        <label class="block">
                                                            <span class="{{ $headerLabelClass }}">Headline</span>
                                                            <input name="sections[{{ $index }}][settings][slides][{{ $slideIndex }}][headline]" value="{{ $slide['headline'] ?? '' }}" class="{{ $headerInputClass }}">
                                                        </label>

                                                        <label class="block">
                                                            <span class="{{ $headerLabelClass }}">Primary CTA label</span>
                                                            <input name="sections[{{ $index }}][settings][slides][{{ $slideIndex }}][primary_cta_label]" value="{{ $slide['primary_cta_label'] ?? '' }}" class="{{ $headerInputClass }}">
                                                        </label>

                                                        <label class="block lg:col-span-2">
                                                            <span class="{{ $headerLabelClass }}">Body</span>
                                                            <textarea name="sections[{{ $index }}][settings][slides][{{ $slideIndex }}][body]" rows="3" class="{{ $headerInputClass }}">{{ $slide['body'] ?? '' }}</textarea>
                                                        </label>

                                                        <label class="block">
                                                            <span class="{{ $headerLabelClass }}">Primary CTA URL</span>
                                                            <input name="sections[{{ $index }}][settings][slides][{{ $slideIndex }}][primary_cta_url]" value="{{ $slide['primary_cta_url'] ?? '' }}" class="{{ $headerInputClass }}">
                                                        </label>

                                                        <label class="block">
                                                            <span class="{{ $headerLabelClass }}">Secondary CTA label</span>
                                                            <input name="sections[{{ $index }}][settings][slides][{{ $slideIndex }}][secondary_cta_label]" value="{{ $slide['secondary_cta_label'] ?? '' }}" class="{{ $headerInputClass }}">
                                                        </label>

                                                        <label class="block">
                                                            <span class="{{ $headerLabelClass }}">Secondary CTA URL</span>
                                                            <input name="sections[{{ $index }}][settings][slides][{{ $slideIndex }}][secondary_cta_url]" value="{{ $slide['secondary_cta_url'] ?? '' }}" class="{{ $headerInputClass }}">
                                                        </label>
                                                    </div>
                                                </div>
                                            @endfor
                                        </div>

                                        @error("sections.$index.settings.slides")
                                            <span class="mt-2 block text-sm text-red-600">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center dark:border-gray-700 dark:bg-gray-950" data-homepage-empty-state>
                                    <p class="text-sm font-medium text-slate-700 dark:text-gray-200">
                                        No homepage Hero yet.
                                    </p>

                                    <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
                                        Add Hero to configure the static image or auto slider shown by the active theme.
                                    </p>
                                </div>
                            @endforelse
                        </div>

                        <div class="mt-4 rounded-2xl border border-slate-200/70 bg-slate-50 px-4 py-3 text-sm leading-6 text-slate-600 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-300">
                            Homepage content below the Hero is rendered by the active theme.
                        </div>

                        <template data-homepage-section-template>
                            <fieldset disabled class="contents" data-homepage-section-template-fields>
                            <div class="rounded-2xl border border-slate-200/70 bg-slate-50 p-4 dark:border-gray-700 dark:bg-gray-950" data-homepage-section-row data-homepage-active-slide="0">
                                <input type="hidden" name="sections[__INDEX__][id]" value="">
                                <input type="hidden" name="sections[__INDEX__][section_code]" value="hero" data-homepage-section-code>

                                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-800 dark:text-gray-100">
                                            Hero
                                        </p>

                                        <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">
                                            Static image or auto slider for the homepage opening area.
                                        </p>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-4">
                                        <input type="hidden" name="sections[__INDEX__][is_active]" value="0">
                                        <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-500 dark:text-gray-400">
                                            <input type="checkbox" name="sections[__INDEX__][is_active]" value="1" class="{{ $toggleClass }}" checked>
                                            Enabled
                                        </label>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1fr)_140px]">
                                    <label class="block">
                                        <span class="{{ $headerLabelClass }}">Section title</span>
                                        <input name="sections[__INDEX__][title]" value="Hero" class="{{ $headerInputClass }}" data-homepage-section-title>
                                    </label>

                                    <label class="block">
                                        <span class="{{ $headerLabelClass }}">Order</span>
                                        <input type="number" min="0" name="sections[__INDEX__][sort_order]" value="__NUMBER__" class="{{ $headerInputClass }}">
                                    </label>
                                </div>

                                <div class="mt-5" data-homepage-fields="hero">
                                    <input type="hidden" name="sections[__INDEX__][settings][mode]" value="static" data-homepage-hero-mode-value>

                                    <div class="mb-4 flex flex-wrap items-center gap-2 rounded-2xl border border-slate-200 bg-white p-2 dark:border-gray-700 dark:bg-gray-800">
                                        <label class="cursor-pointer">
                                            <input type="radio" name="sections[__INDEX__][settings][mode_toggle]" value="static" class="peer sr-only" data-homepage-hero-mode onchange="window.cmsHomepageHeroModeChanged?.(this)" checked>
                                            <span class="inline-flex rounded-xl px-4 py-2 text-sm font-medium text-slate-500 transition peer-checked:bg-blue-50 peer-checked:text-blue-600 dark:text-gray-400 dark:peer-checked:bg-blue-950/40 dark:peer-checked:text-blue-300">
                                                Static image
                                            </span>
                                        </label>

                                        <label class="cursor-pointer">
                                            <input type="radio" name="sections[__INDEX__][settings][mode_toggle]" value="slider" class="peer sr-only" data-homepage-hero-mode onchange="window.cmsHomepageHeroModeChanged?.(this)">
                                            <span class="inline-flex rounded-xl px-4 py-2 text-sm font-medium text-slate-500 transition peer-checked:bg-blue-50 peer-checked:text-blue-600 dark:text-gray-400 dark:peer-checked:bg-blue-950/40 dark:peer-checked:text-blue-300">
                                                Auto slider
                                            </span>
                                        </label>
                                    </div>

                                    <div class="mb-4 hidden rounded-2xl border border-slate-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800" data-homepage-hero-slide-tabs>
                                        <div class="flex flex-wrap items-center gap-2">
                                            @for ($slideIndex = 0; $slideIndex < 5; $slideIndex++)
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-xs font-semibold transition {{ $slideIndex === 0 ? 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-300' : 'hidden border-slate-200 bg-slate-50 text-slate-500 hover:border-blue-200 hover:text-blue-600 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-400 dark:hover:border-blue-900 dark:hover:text-blue-300' }}"
                                                    data-homepage-hero-slide-tab
                                                    data-slide-index="{{ $slideIndex }}"
                                                    onclick="window.cmsHomepageHeroSelectSlide?.(this)"
                                                >
                                                    <span class="flex h-8 w-10 items-center justify-center rounded-lg bg-white text-xs text-slate-400 dark:bg-gray-900 dark:text-gray-500">
                                                        {{ $slideIndex + 1 }}
                                                    </span>
                                                    Slide {{ $slideIndex + 1 }}
                                                </button>
                                            @endfor
                                        </div>
                                    </div>

                                    <div>
                                        @for ($slideIndex = 0; $slideIndex < 5; $slideIndex++)
                                            @php
                                                $isExtraSlide = $slideIndex > 0;
                                            @endphp

                                            <div class="rounded-2xl border border-slate-200/70 bg-white p-4 dark:border-gray-700 dark:bg-gray-800 {{ $isExtraSlide ? 'hidden' : '' }}" data-homepage-hero-slide-panel data-slide-index="{{ $slideIndex }}">
                                                <input type="hidden" name="sections[__INDEX__][settings][slides][{{ $slideIndex }}][enabled]" value="{{ $slideIndex === 0 ? 1 : 0 }}" data-homepage-hero-slide-enabled>

                                                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                                                    <div>
                                                        <p class="text-sm font-semibold text-slate-800 dark:text-gray-100">
                                                            {{ $slideIndex === 0 ? 'Primary hero slide' : 'Slide '.($slideIndex + 1) }}
                                                        </p>

                                                        <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">
                                                            {{ $slideIndex === 0 ? 'Used by static mode and as the first slider frame.' : 'Only used when Auto slider is selected.' }}
                                                        </p>
                                                    </div>

                                                    <button
                                                        type="button"
                                                        class="{{ $slideIndex === 0 ? 'hidden' : '' }} text-xs font-semibold text-red-500 transition hover:text-red-600"
                                                        data-homepage-remove-hero-slide
                                                        onclick="window.cmsHomepageHeroRemoveSlide?.(this)"
                                                    >
                                                        Remove
                                                    </button>
                                                </div>

                                                <input type="hidden" name="sections[__INDEX__][settings][slides][{{ $slideIndex }}][current_image]" value="" data-homepage-hero-current-image>

                                                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                                                    <label class="block">
                                                        <span class="{{ $headerLabelClass }}">Image</span>
                                                        <input type="file" name="sections[__INDEX__][settings][slides][{{ $slideIndex }}][image_file]" accept="image/png,image/jpeg,image/jpg,image/webp" class="{{ $headerInputClass }}">
                                                    </label>

                                                    <label class="block">
                                                        <span class="{{ $headerLabelClass }}">Alt / title</span>
                                                        <input name="sections[__INDEX__][settings][slides][{{ $slideIndex }}][title]" class="{{ $headerInputClass }}">
                                                    </label>

                                                    <label class="block">
                                                        <span class="{{ $headerLabelClass }}">Headline</span>
                                                        <input name="sections[__INDEX__][settings][slides][{{ $slideIndex }}][headline]" class="{{ $headerInputClass }}">
                                                    </label>

                                                    <label class="block">
                                                        <span class="{{ $headerLabelClass }}">Primary CTA label</span>
                                                        <input name="sections[__INDEX__][settings][slides][{{ $slideIndex }}][primary_cta_label]" class="{{ $headerInputClass }}">
                                                    </label>

                                                    <label class="block lg:col-span-2">
                                                        <span class="{{ $headerLabelClass }}">Body</span>
                                                        <textarea name="sections[__INDEX__][settings][slides][{{ $slideIndex }}][body]" rows="3" class="{{ $headerInputClass }}"></textarea>
                                                    </label>

                                                    <label class="block">
                                                        <span class="{{ $headerLabelClass }}">Primary CTA URL</span>
                                                        <input name="sections[__INDEX__][settings][slides][{{ $slideIndex }}][primary_cta_url]" class="{{ $headerInputClass }}">
                                                    </label>

                                                    <label class="block">
                                                        <span class="{{ $headerLabelClass }}">Secondary CTA label</span>
                                                        <input name="sections[__INDEX__][settings][slides][{{ $slideIndex }}][secondary_cta_label]" class="{{ $headerInputClass }}">
                                                    </label>

                                                    <label class="block">
                                                        <span class="{{ $headerLabelClass }}">Secondary CTA URL</span>
                                                        <input name="sections[__INDEX__][settings][slides][{{ $slideIndex }}][secondary_cta_url]" class="{{ $headerInputClass }}">
                                                    </label>
                                                </div>
                                            </div>
                                        @endfor
                                    </div>
                                </div>
                            </div>
                            </fieldset>
                        </template>
                    </div>
                </form>
            @elseif ($editor['type'] === 'footer')
                <form
                    id="cms-studio-form"
                    method="POST"
                    action="{{ $editor['form_action'] }}"
                    class="space-y-6"
                >
                    @csrf

                    <div class="{{ $headerSectionClass }}">
                        <h3 class="{{ $headerSectionTitleClass }}">
                            Identity
                        </h3>

                        <p class="{{ $headerSectionDescriptionClass }}">
                            Set the footer name, logo, and theme-supported layout.
                        </p>

                        <div class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-2">
                            <label class="block">
                                <span class="{{ $headerLabelClass }}">Footer name</span>
                                <input name="name" value="{{ old('name', $values['name'] ?? '') }}" class="{{ $headerInputClass }}">
                                @error('name')
                                    <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                                @enderror
                            </label>

                            <label class="block">
                                <span class="{{ $headerLabelClass }}">Footer variant</span>
                                <select name="variant" class="{{ $headerInputClass }}">
                                    @foreach ($editor['variants'] as $variant => $label)
                                        <option value="{{ $variant }}" @selected(old('variant', $values['variant'] ?? 'simple') === $variant)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('variant')
                                    <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                                @enderror
                            </label>

                            <label class="block md:col-span-2">
                                <span class="{{ $headerLabelClass }}">Footer logo URL</span>
                                <input name="logo_url" value="{{ old('logo_url', $values['logo_url'] ?? '') }}" class="{{ $headerInputClass }}">
                                <span class="{{ $headerHelperClass }}">Use an image URL for now. Media picker will be added later.</span>
                                @error('logo_url')
                                    <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                                @enderror
                            </label>
                        </div>
                    </div>

                    <div class="{{ $headerSectionClass }}">
                        <h3 class="{{ $headerSectionTitleClass }}">
                            Newsletter
                        </h3>

                        <p class="{{ $headerSectionDescriptionClass }}">
                            Control the email signup message shown in the footer.
                        </p>

                        <input type="hidden" name="newsletter_enabled" value="0">

                        <label class="mt-5 {{ $headerToggleRowClass }}">
                            <span>
                                <span class="block text-sm font-medium text-slate-700 dark:text-gray-300">Newsletter enabled</span>
                                <span class="{{ $headerHelperClass }}">Display the newsletter signup area in the footer.</span>
                            </span>

                            <input
                                type="checkbox"
                                name="newsletter_enabled"
                                value="1"
                                class="{{ $toggleClass }} shrink-0"
                                @checked(old('newsletter_enabled', $values['newsletter_enabled'] ?? false))
                            >
                        </label>

                        <div class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-2">
                            <label class="block">
                                <span class="{{ $headerLabelClass }}">Newsletter heading</span>
                                <input name="newsletter_heading" value="{{ old('newsletter_heading', $values['newsletter_heading'] ?? '') }}" class="{{ $headerInputClass }}">
                                @error('newsletter_heading')
                                    <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                                @enderror
                            </label>

                            <label class="block">
                                <span class="{{ $headerLabelClass }}">Newsletter text</span>
                                <input name="newsletter_text" value="{{ old('newsletter_text', $values['newsletter_text'] ?? '') }}" class="{{ $headerInputClass }}">
                                @error('newsletter_text')
                                    <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                                @enderror
                            </label>
                        </div>
                    </div>

                    <div class="{{ $headerSectionClass }}">
                        <h3 class="{{ $headerSectionTitleClass }}">
                            Contact
                        </h3>

                        <p class="{{ $headerSectionDescriptionClass }}">
                            Store contact details shown in the footer.
                        </p>

                        <div class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-2">
                            <label class="block">
                                <span class="{{ $headerLabelClass }}">Contact email</span>
                                <input name="contact_email" value="{{ old('contact_email', $values['contact_email'] ?? '') }}" class="{{ $headerInputClass }}">
                                @error('contact_email')
                                    <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                                @enderror
                            </label>

                            <label class="block">
                                <span class="{{ $headerLabelClass }}">Contact phone</span>
                                <input name="contact_phone" value="{{ old('contact_phone', $values['contact_phone'] ?? '') }}" class="{{ $headerInputClass }}">
                                @error('contact_phone')
                                    <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                                @enderror
                            </label>
                        </div>
                    </div>

                    <div class="{{ $headerSectionClass }}">
                        <h3 class="{{ $headerSectionTitleClass }}">
                            Social Links
                        </h3>

                        <p class="{{ $headerSectionDescriptionClass }}">
                            Add social profile URLs used by footer-capable themes.
                        </p>

                        <div class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-2">
                            @foreach ([
                                'social_facebook' => 'Facebook URL',
                                'social_instagram' => 'Instagram URL',
                                'social_x' => 'X URL',
                                'social_youtube' => 'YouTube URL',
                                'social_tiktok' => 'TikTok URL',
                            ] as $field => $label)
                                <label class="block">
                                    <span class="{{ $headerLabelClass }}">{{ $label }}</span>
                                    <input name="{{ $field }}" value="{{ old($field, $values[$field] ?? '') }}" class="{{ $headerInputClass }}">
                                    @error($field)
                                        <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                                    @enderror
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="{{ $headerSectionClass }}">
                        <h3 class="{{ $headerSectionTitleClass }}">
                            Legal Text
                        </h3>

                        <label class="mt-5 block">
                            <span class="{{ $headerLabelClass }}">Copyright text</span>
                            <input name="copyright_text" value="{{ old('copyright_text', $values['copyright_text'] ?? '') }}" class="{{ $headerInputClass }}">
                            @error('copyright_text')
                                <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                            @enderror
                        </label>
                    </div>
                </form>
            @else
                @php
                    $meta = $editor['meta'] ?? [];
                    $summary = $meta['summary'] ?? [];
                    $summaryCounts = collect([
                        'total' => 'Total',
                        'active' => 'Active',
                        'published' => 'Published',
                        'draft' => 'Draft',
                        'items' => 'Items',
                    ])->filter(fn ($label, $key) => array_key_exists($key, $summary))->all();
                @endphp

                <div class="space-y-6">
                    <div class="rounded-2xl border border-slate-200/70 bg-slate-50 p-6 dark:border-gray-700 dark:bg-gray-950">
                        <h3 class="{{ $panelTitleClass }}">
                            {{ $editor['title'] }}
                        </h3>

                        <p class="mt-2 {{ $bodyTextClass }}">
                            {{ $editor['description'] }}
                        </p>

                        @if (! empty($editor['note']))
                            <p class="mt-5 rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm leading-6 text-blue-700 dark:border-blue-900 dark:bg-blue-950/30 dark:text-blue-300">
                                {{ $editor['note'] }}
                            </p>
                        @endif
                    </div>

                    @if (! empty($summaryCounts))
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($summaryCounts as $key => $label)
                                <div class="rounded-2xl border border-slate-200/70 p-4 dark:border-gray-700">
                                    <p class="text-xs font-medium text-slate-500 dark:text-gray-400">{{ $label }}</p>
                                    <p class="mt-2 font-sans text-2xl font-semibold text-slate-950 dark:text-white">{{ $summary[$key] }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if (! empty($summary['menus']))
                        <div class="{{ $headerSectionClass }}">
                            <h3 class="{{ $headerSectionTitleClass }}">Current Menus</h3>

                            <div class="mt-4 divide-y divide-slate-200 dark:divide-gray-700">
                                @foreach ($summary['menus'] as $menu)
                                    <div class="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0">
                                        <div>
                                            <p class="text-sm font-medium text-slate-800 dark:text-gray-100">{{ $menu['name'] }}</p>
                                            <p class="text-xs text-slate-500 dark:text-gray-400">{{ $menu['location'] ?: 'No location set' }}</p>
                                        </div>

                                        <span class="text-xs font-medium text-slate-500 dark:text-gray-400">{{ $menu['items_count'] }} items</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if (! empty($meta['sections']['sections']))
                        <div class="{{ $headerSectionClass }}">
                            <h3 class="{{ $headerSectionTitleClass }}">Current Homepage Sections</h3>

                            <div class="mt-4 space-y-3">
                                @foreach ($meta['sections']['sections'] as $section)
                                    <div class="flex items-center justify-between gap-4 rounded-xl bg-slate-50 px-4 py-3 dark:bg-gray-950">
                                        <div>
                                            <p class="text-sm font-medium text-slate-800 dark:text-gray-100">{{ $section['title'] }}</p>
                                            <p class="text-xs text-slate-500 dark:text-gray-400">{{ $section['type'] }}</p>
                                        </div>

                                        <span class="rounded-full px-3 py-1 text-xs font-medium {{ $section['is_active'] ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-slate-100 text-slate-500 dark:bg-gray-700 dark:text-gray-300' }}">
                                            {{ $section['is_active'] ? 'Enabled' : 'Disabled' }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if (! empty($summary['pages']) || ! empty($summary['blocks']))
                        <div class="{{ $headerSectionClass }}">
                            <h3 class="{{ $headerSectionTitleClass }}">
                                {{ ! empty($summary['pages']) ? 'Current Pages' : 'Current Blocks' }}
                            </h3>

                            <div class="mt-4 divide-y divide-slate-200 dark:divide-gray-700">
                                @foreach (($summary['pages'] ?? $summary['blocks']) as $item)
                                    <div class="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0">
                                        <div>
                                            <p class="text-sm font-medium text-slate-800 dark:text-gray-100">{{ $item['title'] }}</p>
                                            <p class="text-xs text-slate-500 dark:text-gray-400">{{ $item['slug'] }} · {{ $item['type'] }}</p>
                                        </div>

                                        <span class="text-xs font-medium text-slate-500 dark:text-gray-400">{{ ucfirst((string) $item['status']) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @foreach ([
                        'supported_sections' => 'Theme-Supported Sections',
                        'allowed_types' => 'Allowed Page Types',
                        'recommended_types' => 'Recommended Block Types',
                        'groups' => 'Setting Groups',
                    ] as $metaKey => $heading)
                        @if (! empty($meta[$metaKey]))
                            <div class="{{ $headerSectionClass }}">
                                <h3 class="{{ $headerSectionTitleClass }}">{{ $heading }}</h3>

                                <div class="mt-4 flex flex-wrap gap-2">
                                    @foreach ($meta[$metaKey] as $item)
                                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600 dark:bg-gray-700 dark:text-gray-300">
                                            {{ is_array($item) ? ($item['name'] ?? $item['code'] ?? '') : $item }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach

                    @if (! empty($meta['next_steps']))
                        <div class="{{ $headerSectionClass }}">
                            <h3 class="{{ $headerSectionTitleClass }}">Next Builder Steps</h3>

                            <ul class="mt-4 space-y-2 text-sm leading-6 text-slate-500 dark:text-gray-400">
                                @foreach ($meta['next_steps'] as $step)
                                    <li class="flex gap-2">
                                        <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-blue-500"></span>
                                        <span>{{ $step }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endif
        </section>

            <aside class="{{ $panelClass }} min-w-0" data-preview-shell>
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <h2 class="{{ $panelTitleClass }}">
                    {{ $preview['title'] }}
                </h2>

                <div class="flex rounded-xl border border-slate-200 bg-white p-1 dark:border-gray-700 dark:bg-gray-800" data-preview-tabs>
                    <button type="button" class="rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-600 dark:bg-blue-950/40 dark:text-blue-300" data-preview-mode="desktop">Desktop</button>
                    <button type="button" class="rounded-lg px-3 py-1.5 text-xs font-medium text-slate-500 transition hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-300" data-preview-mode="tablet">Tablet</button>
                    <button type="button" class="rounded-lg px-3 py-1.5 text-xs font-medium text-slate-500 transition hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-300" data-preview-mode="mobile">Mobile</button>
                </div>
            </div>

            <div class="rounded-[24px] border border-slate-200 bg-slate-50 p-6 dark:border-gray-700 dark:bg-gray-950">
                <div class="cms-studio-preview-viewport cms-preview-desktop" data-preview-viewport>
                    @if ($preview['type'] === 'header')
                        @php
                            $previewValues = $preview['values'];
                            $previewValues['logo_url'] = old('logo_url', $previewValues['logo_url'] ?? null);
                            $previewValues['announcement_enabled'] = old('announcement_enabled', $previewValues['announcement_enabled'] ?? false);
                            $previewValues['announcement_text'] = old('announcement_text', $previewValues['announcement_text'] ?? null);
                            $previewValues['show_search'] = old('show_search', $previewValues['show_search'] ?? true);
                            $previewValues['show_account'] = old('show_account', $previewValues['show_account'] ?? true);
                            $previewValues['show_cart'] = old('show_cart', $previewValues['show_cart'] ?? true);
                            $previewValues['sticky'] = old('sticky', $previewValues['sticky'] ?? false);
                            $previewValues['variant'] = old('variant', $previewValues['variant'] ?? 'classic');
                            $selectedMenuLabel = collect($editor['menus'] ?? [])->first(fn ($menu) => (string) ($menu['id'] ?? '') === (string) ($previewValues['menu_id'] ?? ''))['name'] ?? 'No menu selected';
                            $initialNavLabels = $selectedMenuLabel === 'No menu selected'
                                ? ['Home', 'Shop', 'Categories', 'Contact']
                                : array_values(array_unique(array_merge([$selectedMenuLabel], $preview['navigation_labels'] ?? [])));
                        @endphp

                        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-none dark:border-gray-700 dark:bg-gray-800" data-header-preview>
                            <div
                                class="{{ $previewValues['announcement_enabled'] ? '' : 'hidden' }} bg-slate-950 px-4 py-2 text-center text-xs font-medium text-white dark:bg-gray-800"
                                data-header-announcement
                            >
                                <span data-header-announcement-text>{{ $previewValues['announcement_text'] ?: 'Announcement text' }}</span>
                            </div>

                            <div class="cms-header-preview-main gap-4 px-6 py-5" data-header-main data-header-variant="{{ $previewValues['variant'] }}">
                                <span class="cms-preview-mobile-only h-10 w-10 shrink-0 flex-col items-center justify-center gap-1 rounded-xl border border-slate-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                                    <span class="h-0.5 w-5 rounded-full bg-slate-500 dark:bg-gray-400"></span>
                                    <span class="h-0.5 w-5 rounded-full bg-slate-500 dark:bg-gray-400"></span>
                                    <span class="h-0.5 w-5 rounded-full bg-slate-500 dark:bg-gray-400"></span>
                                </span>

                                <div class="flex min-w-0 items-center gap-3" data-header-brand>
                                    <img
                                        src="{{ $previewValues['logo_url'] }}"
                                        alt=""
                                        class="{{ $previewValues['logo_url'] ? '' : 'hidden' }} h-10 max-w-[180px] object-contain"
                                        data-header-logo-image
                                        onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');"
                                    >

                                    <div class="{{ $previewValues['logo_url'] ? 'hidden' : '' }} font-sans text-lg font-bold tracking-tight text-slate-950 dark:text-white" data-header-logo-text>
                                        Storefront
                                    </div>
                                </div>

                                <nav class="cms-preview-desktop-only flex min-w-0 items-center justify-center gap-6" data-header-nav>
                                    @foreach ($initialNavLabels as $label)
                                        <span class="text-sm font-medium text-slate-600 dark:text-gray-300">{{ $label }}</span>
                                    @endforeach
                                </nav>

                                <div class="flex shrink-0 items-center justify-end gap-2" data-header-actions>
                                    <span class="{{ $previewValues['sticky'] ? '' : 'hidden' }} inline-flex items-center justify-center rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-sm font-medium text-blue-600 dark:border-blue-800 dark:bg-blue-950/40 dark:text-blue-300" data-header-sticky>
                                        Sticky
                                    </span>

                                    <span class="{{ $previewValues['show_search'] ? '' : 'hidden' }} inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300" data-header-search>
                                        Search
                                    </span>

                                    <span class="{{ $previewValues['show_account'] ? '' : 'hidden' }} inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300" data-header-account>
                                        Account
                                    </span>

                                    <span class="{{ $previewValues['show_cart'] ? '' : 'hidden' }} inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300" data-header-cart>
                                        Cart
                                    </span>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 border-t border-slate-200 bg-slate-50 px-6 py-3 text-xs text-slate-500 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-400">
                                <span data-header-menu-label>{{ $selectedMenuLabel }}</span>
                            </div>
                        </div>
                    @elseif ($preview['type'] === 'footer')
                        @php
                            $previewValues = $preview['values'];
                            $previewValues['newsletter_enabled'] = old('newsletter_enabled', $previewValues['newsletter_enabled'] ?? false);
                            $previewValues['newsletter_heading'] = old('newsletter_heading', $previewValues['newsletter_heading'] ?? null);
                            $previewValues['newsletter_text'] = old('newsletter_text', $previewValues['newsletter_text'] ?? null);
                            $previewValues['contact_email'] = old('contact_email', $previewValues['contact_email'] ?? null);
                            $previewValues['contact_phone'] = old('contact_phone', $previewValues['contact_phone'] ?? null);
                            $previewValues['copyright_text'] = old('copyright_text', $previewValues['copyright_text'] ?? null);
                        @endphp

                        <div class="overflow-hidden rounded-[24px] border border-slate-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                            <div class="grid grid-cols-1 gap-8 p-6 lg:grid-cols-[minmax(0,1fr)_minmax(320px,420px)]">
                                <div>
                                    <p class="font-sans text-lg font-semibold text-slate-950 dark:text-white">Storefront</p>
                                    <div class="mt-4 space-y-2 text-sm text-slate-500 dark:text-gray-400">
                                        <p>{{ $previewValues['contact_email'] ?: 'hello@example.com' }}</p>
                                        <p>{{ $previewValues['contact_phone'] ?: '+1 555 0100' }}</p>
                                    </div>

                                    <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-3">
                                        @for ($column = 0; $column < 3; $column++)
                                            <div class="space-y-3">
                                                <div class="h-3 w-20 rounded-full bg-slate-300 dark:bg-gray-700"></div>
                                                <div class="h-2 rounded-full bg-slate-200 dark:bg-gray-800"></div>
                                                <div class="h-2 w-4/5 rounded-full bg-slate-200 dark:bg-gray-800"></div>
                                                <div class="h-2 w-2/3 rounded-full bg-slate-200 dark:bg-gray-800"></div>
                                            </div>
                                        @endfor
                                    </div>
                                </div>

                                @if ($previewValues['newsletter_enabled'])
                                    <div class="rounded-2xl bg-slate-50 p-5 dark:bg-gray-950">
                                        <p class="font-medium text-slate-900 dark:text-white">{{ $previewValues['newsletter_heading'] ?: 'Join our newsletter' }}</p>
                                        <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-gray-400">{{ $previewValues['newsletter_text'] ?: 'Get updates and offers from the store.' }}</p>
                                        <div class="mt-5 flex overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                                            <div class="h-11 flex-1"></div>
                                            <div class="w-28 bg-blue-600"></div>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <p class="border-t border-slate-200 px-6 py-4 text-sm text-slate-500 dark:border-gray-700 dark:text-gray-400">
                                {{ $previewValues['copyright_text'] ?: 'Copyright Storefront. All rights reserved.' }}
                            </p>
                        </div>
                    @elseif ($preview['type'] === 'navigation')
                        @php
                            $previewValues = $preview['values'] ?? [];
                            $previewItems = collect($previewValues['items'] ?? [])
                                ->filter(fn ($item) => ! empty($item['title']) && ! empty($item['target']));
                        @endphp

                        <div class="overflow-hidden rounded-[24px] border border-slate-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 px-6 py-5 dark:border-gray-700">
                                <div>
                                    <p class="font-sans text-lg font-semibold text-slate-950 dark:text-white">
                                        {{ $previewValues['name'] ?: 'New Menu' }}
                                    </p>

                                    <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
                                        {{ $editor['locations'][$previewValues['location'] ?? 'header'] ?? 'Header Navigation' }}
                                    </p>
                                </div>

                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ ! empty($previewValues['is_active']) ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-slate-100 text-slate-500 dark:bg-gray-700 dark:text-gray-300' }}">
                                    {{ ! empty($previewValues['is_active']) ? 'Active' : 'Inactive' }}
                                </span>
                            </div>

                            @if ($previewItems->isNotEmpty())
                                <nav class="flex flex-wrap gap-3 p-6">
                                    @foreach ($previewItems as $item)
                                        <span class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                            {{ $item['title'] }}

                                            @if (! empty($item['open_in_new_tab']))
                                                <span class="text-xs text-slate-400 dark:text-gray-500">New tab</span>
                                            @endif
                                        </span>
                                    @endforeach
                                </nav>
                            @else
                                <p class="p-6 text-sm leading-6 text-slate-500 dark:text-gray-400">
                                    Add menu items to preview the storefront navigation.
                                </p>
                            @endif
                        </div>
                    @elseif ($preview['type'] === 'homepage')
                        @php
                            $homepagePreviewValues = $preview['values'] ?? [];
                            $homepagePreviewSections = collect($homepagePreviewValues['sections'] ?? [])
                                ->filter(fn ($section) => ! empty($section['is_active']) && ($section['section_code'] ?? null) === 'hero')
                                ->sortBy('sort_order')
                                ->values();
                        @endphp

                        <div class="overflow-hidden rounded-[24px] border border-slate-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 px-6 py-5 dark:border-gray-700">
                                <div>
                                    <p class="font-sans text-lg font-semibold text-slate-950 dark:text-white">
                                        {{ $homepagePreviewValues['page']['title'] ?? 'Homepage' }}
                                    </p>

                                    <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
                                        Saved Hero preview · below-Hero content comes from the active theme
                                    </p>
                                </div>

                                <a
                                    href="{{ $previewStorefrontUrl }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="{{ $secondaryButtonClass }}"
                                >
                                    Open Storefront
                                </a>
                            </div>

                            @if ($homepagePreviewSections->isNotEmpty())
                                <div class="space-y-4 bg-slate-50 p-5 dark:bg-gray-950">
                                    @foreach ($homepagePreviewSections as $section)
                                        @php
                                            $previewSectionSettings = array_replace($emptyHomepageSettings, $section['settings'] ?? []);
                                        @endphp

                                        @if (($section['section_code'] ?? '') === 'hero')
                                            @php
                                                $previewSlides = collect($previewSectionSettings['slides'] ?? [])
                                                    ->filter(fn ($slide) => ! empty($slide['image']))
                                                    ->values();
                                            @endphp

                                            <section class="overflow-hidden rounded-[24px] bg-white dark:bg-gray-800">
                                                @if ($previewSlides->isNotEmpty())
                                                    <div class="grid grid-cols-1 gap-5 p-5 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
                                                        <div class="flex flex-col justify-center">
                                                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-blue-600 dark:text-blue-300">
                                                                {{ ($previewSectionSettings['mode'] ?? 'static') === 'slider' ? 'Auto Slider' : 'Static Hero' }}
                                                            </p>

                                                            <h3 class="mt-3 max-w-2xl font-sans text-3xl font-bold tracking-tight text-slate-950 dark:text-white">
                                                                {{ $previewSlides->first()['headline'] ?: ($previewSlides->first()['title'] ?? ($section['title'] ?? 'Hero')) }}
                                                            </h3>

                                                            @if (! empty($previewSlides->first()['body']))
                                                                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-500 dark:text-gray-400">
                                                                    {{ $previewSlides->first()['body'] }}
                                                                </p>
                                                            @endif

                                                            <div class="mt-5 flex flex-wrap gap-2">
                                                                @if (! empty($previewSlides->first()['primary_cta_label']))
                                                                    <span class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white">
                                                                        {{ $previewSlides->first()['primary_cta_label'] }}
                                                                    </span>
                                                                @endif

                                                                @if (! empty($previewSlides->first()['secondary_cta_label']))
                                                                    <span class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                                                        {{ $previewSlides->first()['secondary_cta_label'] }}
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <div class="relative overflow-hidden rounded-2xl">
                                                            <img
                                                                src="{{ \Illuminate\Support\Str::startsWith($previewSlides->first()['image'], ['http://', 'https://', '/']) ? $previewSlides->first()['image'] : asset($previewSlides->first()['image']) }}"
                                                                alt="{{ $previewSlides->first()['title'] ?? '' }}"
                                                                class="aspect-[16/9] w-full object-cover"
                                                            >

                                                            @if ($previewSlides->count() > 1)
                                                                <div class="absolute bottom-4 left-0 flex w-full justify-center gap-2">
                                                                    @foreach ($previewSlides as $previewSlide)
                                                                        <span class="h-2 w-2 rounded-full {{ $loop->first ? 'bg-blue-600' : 'bg-white/80' }}"></span>
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                                                        <p class="text-sm font-semibold text-slate-800 dark:text-gray-100">
                                                            {{ $section['title'] ?? 'Hero' }}
                                                        </p>

                                                        <p class="text-xs font-medium text-slate-500 dark:text-gray-400">
                                                            {{ $previewSlides->count() }} {{ \Illuminate\Support\Str::plural('slide', $previewSlides->count()) }}
                                                        </p>
                                                    </div>
                                                @else
                                                    <div class="p-6 text-sm leading-6 text-slate-500 dark:text-gray-400">
                                                        Upload at least one hero image.
                                                    </div>
                                                @endif
                                            </section>
                                        @elseif (($section['section_code'] ?? '') === 'promo_strip')
                                            <section class="rounded-2xl bg-blue-600 px-5 py-4 text-sm font-semibold text-white">
                                                {{ $previewSectionSettings['content'] ?: ($section['title'] ?? 'Promo Strip') }}
                                            </section>
                                        @elseif (($section['section_code'] ?? '') === 'rich_text')
                                            <section class="rounded-[24px] bg-white p-6 dark:bg-gray-800">
                                                <h3 class="font-sans text-lg font-semibold text-slate-950 dark:text-white">
                                                    {{ $section['title'] ?? 'Rich Text' }}
                                                </h3>

                                                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-500 dark:text-gray-400">
                                                    {{ $previewSectionSettings['content'] }}
                                                </p>
                                            </section>
                                        @else
                                            <section class="rounded-[24px] border border-dashed border-slate-300 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                                                <p class="text-sm font-semibold text-slate-800 dark:text-gray-100">
                                                    {{ $section['title'] ?? $section['section_label'] ?? 'Theme Section' }}
                                                </p>

                                                <p class="mt-1 text-sm leading-6 text-slate-500 dark:text-gray-400">
                                                    This saved theme-managed section is preserved and rendered by the storefront runtime.
                                                </p>
                                            </section>
                                        @endif
                                    @endforeach
                                </div>
                            @else
                                <p class="p-6 text-sm leading-6 text-slate-500 dark:text-gray-400">
                                    Enable homepage sections to preview the saved layout.
                                </p>
                            @endif
                        </div>
                    @else
                        <div class="rounded-[24px] border border-slate-200 bg-white p-8 text-center dark:border-gray-700 dark:bg-gray-800">
                            <p class="{{ $bodyTextClass }}">
                                {{ $preview['description'] }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>
            </aside>
        </section>
    </div>

    @pushOnce('scripts')
        <script>
            (() => {
                const initCmsStudioPreview = () => {
                    document.querySelectorAll('[data-preview-shell]').forEach((shell) => {
                        if (shell.dataset.cmsStudioPreviewBound === '1') {
                            return;
                        }

                        shell.dataset.cmsStudioPreviewBound = '1';

                        const viewport = shell.querySelector('[data-preview-viewport]');
                        const tabs = shell.querySelectorAll('[data-preview-mode]');

                        tabs.forEach((tab) => {
                            tab.addEventListener('click', () => {
                                const activeMode = tab.dataset.previewMode;

                                viewport?.classList.remove('cms-preview-desktop', 'cms-preview-tablet', 'cms-preview-mobile');
                                viewport?.classList.add(`cms-preview-${activeMode}`);

                                tabs.forEach((button) => {
                                    const isActive = button.dataset.previewMode === activeMode;

                                    button.classList.toggle('bg-blue-50', isActive);
                                    button.classList.toggle('text-blue-600', isActive);
                                    button.classList.toggle('dark:bg-blue-950/40', isActive);
                                    button.classList.toggle('dark:text-blue-300', isActive);
                                    button.classList.toggle('text-slate-500', ! isActive);
                                    button.classList.toggle('dark:text-gray-400', ! isActive);
                                });
                            });
                        });
                    });

                    const navigationBuilder = document.querySelector('[data-navigation-builder]');

                    if (navigationBuilder && navigationBuilder.dataset.cmsStudioNavigationBound !== '1') {
                        navigationBuilder.dataset.cmsStudioNavigationBound = '1';

                        const rows = navigationBuilder.querySelector('[data-menu-items]');
                        const template = navigationBuilder.querySelector('[data-menu-item-template]');
                        const addButton = navigationBuilder.querySelector('[data-add-menu-item]');

                        addButton?.addEventListener('click', () => {
                            if (! rows || ! template) {
                                return;
                            }

                            const nextIndex = rows.querySelectorAll('[data-menu-item-row]').length;
                            const nextNumber = nextIndex + 1;
                            const wrapper = document.createElement('div');

                            wrapper.innerHTML = template.innerHTML
                                .replaceAll('__INDEX__', String(nextIndex))
                                .replaceAll('__NUMBER__', String(nextNumber));

                            const newRow = wrapper.firstElementChild;

                            if (newRow) {
                                rows.appendChild(newRow);
                            }
                        });
                    }

                    const homepageBuilder = document.querySelector('[data-homepage-builder]');

                    if (homepageBuilder && homepageBuilder.dataset.cmsStudioHomepageBound !== '1') {
                        homepageBuilder.dataset.cmsStudioHomepageBound = '1';

                        const rows = homepageBuilder.querySelector('[data-homepage-sections]');
                        const template = homepageBuilder.querySelector('[data-homepage-section-template]');
                        const addButton = homepageBuilder.querySelector('[data-add-homepage-section]');

                        const activeTabClasses = [
                            'border-blue-200',
                            'bg-blue-50',
                            'text-blue-700',
                            'dark:border-blue-900',
                            'dark:bg-blue-950/40',
                            'dark:text-blue-300',
                        ];
                        const inactiveTabClasses = [
                            'border-slate-200',
                            'bg-slate-50',
                            'text-slate-500',
                            'hover:border-blue-200',
                            'hover:text-blue-600',
                            'dark:border-gray-700',
                            'dark:bg-gray-950',
                            'dark:text-gray-400',
                            'dark:hover:border-blue-900',
                            'dark:hover:text-blue-300',
                        ];

                        const getHeroRows = () => Array.from(rows?.querySelectorAll('[data-homepage-section-row]') ?? []);
                        const getHeroMode = (row) => row.querySelector('[data-homepage-hero-mode-value]')?.value
                            ?? row.querySelector('[data-homepage-hero-mode]:checked')?.value
                            ?? 'static';
                        const getSlidePanels = (row) => Array.from(row.querySelectorAll('[data-homepage-hero-slide-panel]'));
                        const getSlideTabs = (row) => Array.from(row.querySelectorAll('[data-homepage-hero-slide-tab]'));
                        const isSlideEnabled = (panel, index) => index === 0 || panel.querySelector('[data-homepage-hero-slide-enabled]')?.value === '1';

                        const setSlideEnabled = (panel, enabled) => {
                            const enabledInput = panel.querySelector('[data-homepage-hero-slide-enabled]');

                            if (enabledInput) {
                                enabledInput.value = enabled ? '1' : '0';
                            }
                        };

                        const clearSlidePanel = (panel) => {
                            panel.querySelectorAll('input, textarea').forEach((input) => {
                                if (input.matches('[data-homepage-hero-slide-enabled]')) {
                                    return;
                                }

                                if (input.type === 'file') {
                                    input.value = '';

                                    return;
                                }

                                if (input.matches('[data-homepage-hero-current-image]') || input.type !== 'hidden') {
                                    input.value = '';
                                }
                            });

                            panel.querySelector('img')?.remove();
                        };

                        const setActiveHeroSlide = (row, slideIndex) => {
                            row.dataset.homepageActiveSlide = String(slideIndex);
                            syncHeroRow(row);
                        };

                        const syncHeroRow = (row) => {
                            const mode = getHeroMode(row);
                            const panels = getSlidePanels(row);
                            const tabs = getSlideTabs(row);
                            const tabsWrapper = row.querySelector('[data-homepage-hero-slide-tabs]');

                            if (! panels.length) {
                                return;
                            }

                            setSlideEnabled(panels[0], true);

                            if (mode === 'static') {
                                row.dataset.homepageActiveSlide = '0';
                            }

                            const currentIndex = Number(row.dataset.homepageActiveSlide ?? '0');
                            const activeIndex = mode === 'slider' && currentIndex >= 0 && currentIndex < panels.length
                                ? currentIndex
                                : 0;

                            row.dataset.homepageActiveSlide = String(activeIndex);
                            tabsWrapper?.classList.toggle('hidden', mode !== 'slider');

                            panels.forEach((panel, index) => {
                                const visible = mode === 'static'
                                    ? index === 0
                                    : index === activeIndex;
                                const removeButton = panel.querySelector('[data-homepage-remove-hero-slide]');

                                panel.classList.toggle('hidden', ! visible);
                                removeButton?.classList.toggle('hidden', mode !== 'slider' || index === 0);
                            });

                            tabs.forEach((tab, index) => {
                                const active = index === activeIndex;

                                tab.classList.toggle('hidden', mode !== 'slider');
                                tab.classList.remove(...activeTabClasses, ...inactiveTabClasses);
                                tab.classList.add(...(active ? activeTabClasses : inactiveTabClasses));
                            });
                        };

                        const refreshAddButton = () => {
                            if (! addButton) {
                                return;
                            }

                            const hasHero = getHeroRows().length > 0;

                            addButton.disabled = hasHero;
                            addButton.textContent = hasHero ? 'Hero already added' : 'Add Hero';
                            addButton.classList.toggle('cursor-not-allowed', hasHero);
                            addButton.classList.toggle('opacity-60', hasHero);
                        };

                        const bindHomepageRow = (row) => {
                            if (! row.dataset.homepageActiveSlide) {
                                row.dataset.homepageActiveSlide = '0';
                            }

                            syncHeroRow(row);
                        };

                        window.cmsHomepageHeroModeChanged = (input) => {
                            const row = input?.closest('[data-homepage-section-row]');

                            if (! row) {
                                return;
                            }

                            row.querySelectorAll('[data-homepage-hero-mode]').forEach((modeInput) => {
                                const isSelected = modeInput === input;

                                modeInput.checked = isSelected;
                                modeInput.toggleAttribute('checked', isSelected);
                            });

                            const modeValue = row.querySelector('[data-homepage-hero-mode-value]');

                            if (modeValue) {
                                modeValue.value = input.value;
                            }

                            row.dataset.homepageActiveSlide = '0';
                            syncHeroRow(row);
                        };

                        window.cmsHomepageHeroSelectSlide = (button) => {
                            const row = button?.closest('[data-homepage-section-row]');
                            const panel = row?.querySelector(`[data-homepage-hero-slide-panel][data-slide-index="${button?.dataset.slideIndex ?? '0'}"]`);

                            if (! row) {
                                return;
                            }

                            if (panel) {
                                setSlideEnabled(panel, true);
                            }

                            setActiveHeroSlide(row, Number(button?.dataset.slideIndex ?? '0'));
                        };

                        window.cmsHomepageHeroRemoveSlide = (button) => {
                            const panel = button?.closest('[data-homepage-hero-slide-panel]');
                            const row = button?.closest('[data-homepage-section-row]');
                            const slideIndex = Number(panel?.dataset.slideIndex ?? '0');

                            if (! panel || ! row || slideIndex === 0) {
                                return;
                            }

                            clearSlidePanel(panel);
                            setSlideEnabled(panel, false);
                            setActiveHeroSlide(row, 0);
                        };

                        window.cmsHomepageHeroAdd = (button) => {
                            if (! rows || ! template || getHeroRows().length > 0) {
                                return;
                            }

                            const nextIndex = getHeroRows().length;
                            const nextNumber = nextIndex + 1;
                            const wrapper = document.createElement('div');

                            wrapper.innerHTML = template.innerHTML
                                .replaceAll('__INDEX__', String(nextIndex))
                                .replaceAll('__NUMBER__', String(nextNumber));

                            const newRow = wrapper.querySelector('[data-homepage-section-row]');

                            if (! newRow) {
                                return;
                            }

                            rows.querySelector('[data-homepage-empty-state]')?.remove();
                            rows.appendChild(newRow);
                            bindHomepageRow(newRow);
                            refreshAddButton();
                            newRow.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start',
                            });
                        };

                        rows?.querySelectorAll('[data-homepage-section-row]').forEach(bindHomepageRow);

                        addButton?.addEventListener('click', () => {
                            window.cmsHomepageHeroAdd(addButton);
                        });

                        homepageBuilder.addEventListener('change', (event) => {
                            if (event.target.matches('[data-homepage-hero-mode]')) {
                                window.cmsHomepageHeroModeChanged(event.target);

                                return;
                            }

                            const panel = event.target.closest('[data-homepage-hero-slide-panel]');
                            const row = event.target.closest('[data-homepage-section-row]');

                            if (panel && row && ! event.target.matches('[data-homepage-hero-current-image]')) {
                                setSlideEnabled(panel, true);
                                syncHeroRow(row);
                            }
                        });

                        homepageBuilder.addEventListener('input', (event) => {
                            const panel = event.target.closest('[data-homepage-hero-slide-panel]');
                            const row = event.target.closest('[data-homepage-section-row]');

                            if (panel && row && ! event.target.matches('[data-homepage-hero-current-image]')) {
                                setSlideEnabled(panel, true);
                                syncHeroRow(row);
                            }
                        });

                        homepageBuilder.addEventListener('click', (event) => {
                            const tab = event.target.closest('[data-homepage-hero-slide-tab]');

                            if (tab) {
                                const row = tab.closest('[data-homepage-section-row]');

                                if (row) {
                                    setActiveHeroSlide(row, Number(tab.dataset.slideIndex ?? '0'));
                                }

                                return;
                            }

                            const removeSlideButton = event.target.closest('[data-homepage-remove-hero-slide]');

                            if (removeSlideButton) {
                                const panel = removeSlideButton.closest('[data-homepage-hero-slide-panel]');
                                const row = removeSlideButton.closest('[data-homepage-section-row]');
                                const slideIndex = Number(panel?.dataset.slideIndex ?? '0');

                                if (panel && row && slideIndex > 0) {
                                    clearSlidePanel(panel);
                                    setSlideEnabled(panel, false);
                                    setActiveHeroSlide(row, 0);
                                }
                            }
                        });

                        refreshAddButton();
                    }

                    const cmsStudioForm = document.querySelector('#cms-studio-form');
                    const headerPreview = document.querySelector('[data-header-preview]');

                    if (! cmsStudioForm || ! headerPreview || cmsStudioForm.dataset.cmsStudioPreviewBound === '1') {
                        return;
                    }

                    cmsStudioForm.dataset.cmsStudioPreviewBound = '1';

                    const inputs = {
                        logoUrl: cmsStudioForm.querySelector('[data-preview-input="logo_url"]'),
                        logoFile: cmsStudioForm.querySelector('[data-logo-file-input]'),
                        announcementEnabled: cmsStudioForm.querySelector('[data-preview-input="announcement_enabled"]'),
                        announcementText: cmsStudioForm.querySelector('[data-preview-input="announcement_text"]'),
                        menuId: cmsStudioForm.querySelector('[data-preview-input="menu_id"]'),
                        showSearch: cmsStudioForm.querySelector('[data-preview-input="show_search"]'),
                        showAccount: cmsStudioForm.querySelector('[data-preview-input="show_account"]'),
                        showCart: cmsStudioForm.querySelector('[data-preview-input="show_cart"]'),
                        sticky: cmsStudioForm.querySelector('[data-preview-input="sticky"]'),
                        variants: cmsStudioForm.querySelectorAll('[data-preview-input="variant"]'),
                    };

                    const nodes = {
                        logoUploadPreview: cmsStudioForm.querySelector('[data-logo-upload-preview]'),
                        logoUploadEmpty: cmsStudioForm.querySelector('[data-logo-upload-empty]'),
                        announcementFields: cmsStudioForm.querySelector('[data-announcement-fields]'),
                        announcement: headerPreview.querySelector('[data-header-announcement]'),
                        announcementText: headerPreview.querySelector('[data-header-announcement-text]'),
                        logoImage: headerPreview.querySelector('[data-header-logo-image]'),
                        logoText: headerPreview.querySelector('[data-header-logo-text]'),
                        main: headerPreview.querySelector('[data-header-main]'),
                        nav: headerPreview.querySelector('[data-header-nav]'),
                        menuLabel: headerPreview.querySelector('[data-header-menu-label]'),
                        search: headerPreview.querySelector('[data-header-search]'),
                        account: headerPreview.querySelector('[data-header-account]'),
                        cart: headerPreview.querySelector('[data-header-cart]'),
                        sticky: headerPreview.querySelector('[data-header-sticky]'),
                    };

                    const setHidden = (node, hidden) => {
                        node?.classList.toggle('hidden', hidden);
                    };

                    const selectedVariant = () => {
                        const checked = [...inputs.variants].find((input) => input.checked);

                        return {
                            value: checked?.value ?? 'classic',
                            label: checked?.dataset.previewLabel ?? 'Classic',
                        };
                    };

                    const setLogoPreview = (logoUrl) => {
                        setHidden(nodes.logoImage, ! logoUrl);
                        setHidden(nodes.logoText, !! logoUrl);
                        setHidden(nodes.logoUploadPreview, ! logoUrl);
                        setHidden(nodes.logoUploadEmpty, !! logoUrl);

                        if (logoUrl) {
                            nodes.logoImage?.setAttribute('src', logoUrl);
                            nodes.logoUploadPreview?.setAttribute('src', logoUrl);
                        }
                    };

                    const selectedMenuLabels = () => {
                        const selected = inputs.menuId?.selectedOptions?.[0];

                        if (! selected?.value) {
                            return ['Home', 'Shop', 'Categories', 'Contact'];
                        }

                        try {
                            const labels = JSON.parse(window.atob(selected.dataset.items ?? ''));

                            return Array.isArray(labels) && labels.length
                                ? [...new Set([selected.textContent.trim(), ...labels])]
                                : [selected.textContent.trim(), 'Shop', 'Contact'];
                        } catch (error) {
                            return [selected.textContent.trim(), 'Shop', 'Contact'];
                        }
                    };

                    const renderNavigation = () => {
                        if (! nodes.nav) {
                            return;
                        }

                        nodes.nav.replaceChildren();

                        selectedMenuLabels().forEach((label) => {
                            const item = document.createElement('span');
                            item.className = 'text-sm font-medium text-slate-600 dark:text-gray-300';
                            item.textContent = label;
                            nodes.nav.appendChild(item);
                        });
                    };

                    const renderHeaderPreview = () => {
                        const logoUrl = inputs.logoUrl?.value.trim() ?? '';
                        const announcementEnabled = inputs.announcementEnabled?.checked ?? false;
                        const selectedMenu = inputs.menuId?.selectedOptions?.[0];
                        const variant = selectedVariant();

                        setHidden(nodes.announcement, ! announcementEnabled);
                        nodes.announcementFields?.classList.toggle('opacity-60', ! announcementEnabled);
                        setLogoPreview(logoUrl);
                        setHidden(nodes.search, ! (inputs.showSearch?.checked ?? false));
                        setHidden(nodes.account, ! (inputs.showAccount?.checked ?? false));
                        setHidden(nodes.cart, ! (inputs.showCart?.checked ?? false));
                        setHidden(nodes.sticky, ! (inputs.sticky?.checked ?? false));
                        nodes.main?.setAttribute('data-header-variant', variant.value);

                        if (nodes.announcementText) {
                            nodes.announcementText.textContent = inputs.announcementText?.value.trim() || 'Announcement text';
                        }

                        if (nodes.menuLabel) {
                            nodes.menuLabel.textContent = selectedMenu?.value ? selectedMenu.textContent.trim() : 'No menu selected';
                        }

                        renderNavigation();
                    };

                    inputs.logoFile?.addEventListener('change', () => {
                        const file = inputs.logoFile.files?.[0];

                        if (! file) {
                            renderHeaderPreview();

                            return;
                        }

                        const previewUrl = URL.createObjectURL(file);
                        setLogoPreview(previewUrl);
                    });

                    [
                        inputs.logoUrl,
                        inputs.announcementEnabled,
                        inputs.announcementText,
                        inputs.menuId,
                        inputs.showSearch,
                        inputs.showAccount,
                        inputs.showCart,
                        inputs.sticky,
                        ...inputs.variants,
                    ].forEach((input) => {
                        input?.addEventListener('input', renderHeaderPreview);
                        input?.addEventListener('change', renderHeaderPreview);
                    });

                    renderHeaderPreview();
                };

                const bootCmsStudioPreview = () => {
                    window.setTimeout(initCmsStudioPreview, 0);
                };

                if (! window.__cmsStudioPreviewBootstrapRegistered) {
                    window.__cmsStudioPreviewBootstrapRegistered = true;

                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', bootCmsStudioPreview, { once: true });
                    } else {
                        bootCmsStudioPreview();
                    }

                    window.addEventListener('load', bootCmsStudioPreview, { once: true });
                    document.addEventListener('livewire:navigated', bootCmsStudioPreview);
                } else {
                    bootCmsStudioPreview();
                }
            })();
        </script>
    @endPushOnce
</x-admin::layouts>
