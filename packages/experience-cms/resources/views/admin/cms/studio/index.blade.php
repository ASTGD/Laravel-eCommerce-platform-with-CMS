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
    $navChipBaseClass = 'inline-flex items-center justify-center rounded-xl px-4 py-2 text-sm font-medium transition';
    $navChipActiveClass = 'bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-300';
    $navChipInactiveClass = 'text-slate-600 hover:bg-slate-100 hover:text-blue-600 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-blue-300';
    $values = $editor['values'] ?? [];
@endphp

<x-admin::layouts>
    <x-slot:title>CMS Studio</x-slot:title>

    @pushOnce('styles')
        <style>
            .cms-studio-area-nav {
                scrollbar-width: thin;
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
                        Save Draft
                    </button>
                @endif
            </div>
        </section>

        <section class="{{ $navClass }}">
            <div class="cms-studio-area-nav flex gap-4 overflow-x-auto pb-1 sm:flex-wrap sm:overflow-visible">
                @foreach ($navigationGroups as $group)
                    <div class="min-w-max sm:min-w-0">
                        <div class="flex flex-nowrap gap-2">
                            @foreach ($group['items'] as $item)
                                <a
                                    href="{{ $item['url'] }}"
                                    class="{{ $navChipBaseClass }} {{ $item['active'] ? $navChipActiveClass : $navChipInactiveClass }}"
                                >
                                    {{ $item['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="{{ $panelClass }}">
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
                    action="{{ $editor['form_action'] }}"
                    class="space-y-6"
                >
                    @csrf

                    <div class="{{ $headerSectionClass }}">
                        <h3 class="{{ $headerSectionTitleClass }}">
                            Brand
                        </h3>

                        <p class="{{ $headerSectionDescriptionClass }}">
                            Set the storefront header identity.
                        </p>

                        <div class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-2">
                            <label class="block">
                                <span class="{{ $headerLabelClass }}">Header name</span>
                                <input name="name" value="{{ old('name', $values['name'] ?? '') }}" class="{{ $headerInputClass }}">
                                @error('name')
                                    <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                                @enderror
                            </label>

                            <label class="block">
                                <span class="{{ $headerLabelClass }}">Logo URL</span>
                                <input name="logo_url" value="{{ old('logo_url', $values['logo_url'] ?? '') }}" class="{{ $headerInputClass }}" data-preview-input="logo_url">
                                <span class="{{ $headerHelperClass }}">Use an image URL for now. Media picker will be added later.</span>
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
                            class="mt-5 grid grid-cols-1 gap-5 transition md:grid-cols-2 {{ old('announcement_enabled', $values['announcement_enabled'] ?? false) ? '' : 'opacity-60' }}"
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

                        <div class="mt-5 grid grid-cols-1 gap-3 md:grid-cols-2">
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

                    <div class="{{ $headerSectionClass }}">
                        <h3 class="{{ $headerSectionTitleClass }}">
                            Style
                        </h3>

                        <p class="{{ $headerSectionDescriptionClass }}">
                            Choose a theme-supported header layout style.
                        </p>

                        <div class="mt-5 grid grid-cols-1 gap-3 md:grid-cols-3">
                            @foreach ($editor['variants'] as $variant => $label)
                                <label class="cms-header-variant-card cursor-pointer rounded-xl border border-slate-200/70 bg-slate-50 p-4 transition dark:border-gray-700 dark:bg-gray-950">
                                    <input
                                        type="radio"
                                        name="variant"
                                        value="{{ $variant }}"
                                        class="sr-only"
                                        data-preview-input="variant"
                                        data-preview-label="{{ $label }}"
                                        @checked(old('variant', $values['variant'] ?? 'classic') === $variant)
                                    >

                                    <span class="block text-sm font-medium text-slate-700 dark:text-gray-300">{{ $label }}</span>
                                    <span class="{{ $headerHelperClass }}">
                                        @if ($variant === 'classic')
                                            Logo left, navigation visible, actions right.
                                        @elseif ($variant === 'centered')
                                            Navigation left, logo centered, actions right.
                                        @else
                                            Logo and actions only with reduced navigation.
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        @error('variant')
                            <span class="mt-2 block text-sm text-red-600">{{ $message }}</span>
                        @enderror

                        <noscript>
                            <label class="mt-5 block max-w-xl">
                                <span class="{{ $headerLabelClass }}">Header variant</span>
                                <select name="variant" class="{{ $headerInputClass }}">
                                    @foreach ($editor['variants'] as $variant => $label)
                                        <option value="{{ $variant }}" @selected(old('variant', $values['variant'] ?? 'classic') === $variant)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                        </noscript>
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

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <label class="block">
                            <span class="{{ $labelClass }}">Footer name</span>
                            <input name="name" value="{{ old('name', $values['name'] ?? '') }}" class="{{ $inputClass }}">
                            @error('name')
                                <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                            @enderror
                        </label>

                        <label class="block">
                            <span class="{{ $labelClass }}">Footer variant</span>
                            <select name="variant" class="{{ $inputClass }}">
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
                    </div>

                    <label class="block">
                        <span class="{{ $labelClass }}">Footer logo URL</span>
                        <input name="logo_url" value="{{ old('logo_url', $values['logo_url'] ?? '') }}" class="{{ $inputClass }}">
                        @error('logo_url')
                            <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </label>

                    <div class="rounded-2xl border border-slate-200/70 p-5 dark:border-gray-700">
                        <input type="hidden" name="newsletter_enabled" value="0">

                        <label class="inline-flex items-center gap-3 text-sm font-medium text-slate-700 dark:text-gray-300">
                            <input
                                type="checkbox"
                                name="newsletter_enabled"
                                value="1"
                                class="{{ $toggleClass }}"
                                @checked(old('newsletter_enabled', $values['newsletter_enabled'] ?? false))
                            >
                            Newsletter enabled
                        </label>

                        <div class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-2">
                            <label class="block">
                                <span class="{{ $labelClass }}">Newsletter heading</span>
                                <input name="newsletter_heading" value="{{ old('newsletter_heading', $values['newsletter_heading'] ?? '') }}" class="{{ $inputClass }}">
                                @error('newsletter_heading')
                                    <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                                @enderror
                            </label>

                            <label class="block">
                                <span class="{{ $labelClass }}">Newsletter text</span>
                                <input name="newsletter_text" value="{{ old('newsletter_text', $values['newsletter_text'] ?? '') }}" class="{{ $inputClass }}">
                                @error('newsletter_text')
                                    <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                                @enderror
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <label class="block">
                            <span class="{{ $labelClass }}">Contact email</span>
                            <input name="contact_email" value="{{ old('contact_email', $values['contact_email'] ?? '') }}" class="{{ $inputClass }}">
                            @error('contact_email')
                                <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                            @enderror
                        </label>

                        <label class="block">
                            <span class="{{ $labelClass }}">Contact phone</span>
                            <input name="contact_phone" value="{{ old('contact_phone', $values['contact_phone'] ?? '') }}" class="{{ $inputClass }}">
                            @error('contact_phone')
                                <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                            @enderror
                        </label>
                    </div>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                        <label class="block">
                            <span class="{{ $labelClass }}">Social Facebook URL</span>
                            <input name="social_facebook" value="{{ old('social_facebook', $values['social_facebook'] ?? '') }}" class="{{ $inputClass }}">
                        </label>

                        <label class="block">
                            <span class="{{ $labelClass }}">Social Instagram URL</span>
                            <input name="social_instagram" value="{{ old('social_instagram', $values['social_instagram'] ?? '') }}" class="{{ $inputClass }}">
                        </label>

                        <label class="block">
                            <span class="{{ $labelClass }}">Social X URL</span>
                            <input name="social_x" value="{{ old('social_x', $values['social_x'] ?? '') }}" class="{{ $inputClass }}">
                        </label>
                    </div>

                    <label class="block">
                        <span class="{{ $labelClass }}">Copyright text</span>
                        <input name="copyright_text" value="{{ old('copyright_text', $values['copyright_text'] ?? '') }}" class="{{ $inputClass }}">
                        @error('copyright_text')
                            <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                        @enderror
                    </label>
                </form>
            @else
                <div class="rounded-2xl border border-slate-200/70 bg-slate-50 p-6 dark:border-gray-700 dark:bg-gray-950">
                    <h3 class="{{ $panelTitleClass }}">
                        {{ $editor['title'] }}
                    </h3>

                    <p class="mt-2 {{ $bodyTextClass }}">
                        {{ $editor['description'] }}
                    </p>

                    @if ($editor['title'] === 'Homepage Sections')
                        <p class="mt-5 rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm leading-6 text-blue-700 dark:border-blue-900 dark:bg-blue-950/30 dark:text-blue-300">
                            Sections are predefined by the active theme. Admins can add, reorder, enable, disable, preview, and publish theme-supported sections later.
                        </p>
                    @elseif (! empty($editor['note']))
                        <p class="mt-5 rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm leading-6 text-blue-700 dark:border-blue-900 dark:bg-blue-950/30 dark:text-blue-300">
                            {{ $editor['note'] }}
                        </p>
                    @endif
                </div>
            @endif
        </section>

        <section class="{{ $panelClass }}" data-preview-shell>
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
                                <span data-header-variant-label>{{ $editor['variants'][$previewValues['variant']] ?? 'Classic' }}</span>
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
                    @else
                        <div class="rounded-[24px] border border-slate-200 bg-white p-8 text-center dark:border-gray-700 dark:bg-gray-800">
                            <p class="{{ $bodyTextClass }}">
                                {{ $preview['description'] }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    </div>

    @pushOnce('scripts')
        <script>
            (() => {
                const initCmsStudioPreview = () => {
                    document.querySelectorAll('[data-preview-shell]').forEach((shell) => {
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

                    const cmsStudioForm = document.querySelector('#cms-studio-form');
                    const headerPreview = document.querySelector('[data-header-preview]');

                    if (! cmsStudioForm || ! headerPreview) {
                        return;
                    }

                    const inputs = {
                        logoUrl: cmsStudioForm.querySelector('[data-preview-input="logo_url"]'),
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
                        variant: headerPreview.querySelector('[data-header-variant-label]'),
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
                        setHidden(nodes.logoImage, ! logoUrl);
                        setHidden(nodes.logoText, !! logoUrl);
                        setHidden(nodes.search, ! (inputs.showSearch?.checked ?? false));
                        setHidden(nodes.account, ! (inputs.showAccount?.checked ?? false));
                        setHidden(nodes.cart, ! (inputs.showCart?.checked ?? false));
                        setHidden(nodes.sticky, ! (inputs.sticky?.checked ?? false));
                        nodes.main?.setAttribute('data-header-variant', variant.value);

                        if (nodes.announcementText) {
                            nodes.announcementText.textContent = inputs.announcementText?.value.trim() || 'Announcement text';
                        }

                        if (nodes.logoImage && logoUrl) {
                            nodes.logoImage.classList.remove('hidden');
                            nodes.logoText?.classList.add('hidden');
                            nodes.logoImage.setAttribute('src', logoUrl);
                        }

                        if (nodes.menuLabel) {
                            nodes.menuLabel.textContent = selectedMenu?.value ? selectedMenu.textContent.trim() : 'No menu selected';
                        }

                        if (nodes.variant) {
                            nodes.variant.textContent = variant.label;
                        }

                        renderNavigation();
                    };

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

                window.addEventListener('load', () => {
                    window.setTimeout(initCmsStudioPreview, 0);
                });
            })();
        </script>
    @endPushOnce
</x-admin::layouts>
