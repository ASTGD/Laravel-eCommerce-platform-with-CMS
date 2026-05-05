@php
    $pageTitleClass = 'font-sans text-2xl leading-8 font-bold tracking-tight text-slate-950 dark:text-white';
    $pageSubtitleClass = 'mt-1 text-sm leading-6 text-slate-500 dark:text-gray-400';
    $shellClass = 'rounded-[24px] border border-slate-200/70 bg-white shadow-none dark:border-gray-800 dark:bg-gray-900';
    $panelTitleClass = 'font-sans text-lg leading-7 font-semibold tracking-normal text-slate-950 dark:text-white';
    $bodyTextClass = 'text-sm leading-6 text-slate-500 dark:text-gray-400';
    $labelClass = 'mb-2 block text-sm font-medium text-slate-700 dark:text-gray-300';
    $inputClass = 'w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-800 outline-none transition focus:border-blue-300 focus:ring-2 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-950 dark:text-white dark:focus:border-blue-700 dark:focus:ring-blue-950';
    $toggleClass = 'h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-950';
    $secondaryButtonClass = 'inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-blue-700 dark:hover:bg-blue-950/40 dark:hover:text-blue-300';
    $primaryButtonClass = 'inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700';
    $values = $editor['values'] ?? [];
@endphp

<x-admin::layouts>
    <x-slot:title>CMS Studio</x-slot:title>

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

        <section class="{{ $shellClass }} overflow-hidden">
            <div class="grid grid-cols-1 lg:grid-cols-[260px_minmax(0,1fr)_minmax(360px,420px)]">
                <aside class="border-b border-slate-200/70 p-5 dark:border-gray-800 lg:border-b-0 lg:border-r">
                    <p class="font-sans text-base font-semibold text-slate-950 dark:text-white">
                        CMS Studio
                    </p>

                    <div class="mt-5 space-y-6">
                        @foreach ($navigationGroups as $group)
                            <div class="space-y-2">
                                <p class="text-sm font-medium text-slate-500 dark:text-gray-400">
                                    {{ $group['title'] }}
                                </p>

                                <div class="space-y-1">
                                    @foreach ($group['items'] as $item)
                                        <a
                                            href="{{ $item['url'] }}"
                                            class="block rounded-xl px-3 py-2 text-sm font-medium transition {{ $item['active'] ? 'bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-300' : 'text-slate-600 hover:bg-slate-100 hover:text-blue-600 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-blue-300' }}"
                                        >
                                            {{ $item['label'] }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </aside>

                <main class="min-w-0 border-b border-slate-200/70 p-6 dark:border-gray-800 lg:border-b-0 lg:border-r">
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

                            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                                <label class="block">
                                    <span class="{{ $labelClass }}">Header name</span>
                                    <input name="name" value="{{ old('name', $values['name'] ?? '') }}" class="{{ $inputClass }}">
                                    @error('name')
                                        <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                                    @enderror
                                </label>

                                <label class="block">
                                    <span class="{{ $labelClass }}">Header variant</span>
                                    <select name="variant" class="{{ $inputClass }}">
                                        @foreach ($editor['variants'] as $variant => $label)
                                            <option value="{{ $variant }}" @selected(old('variant', $values['variant'] ?? 'classic') === $variant)>
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
                                <span class="{{ $labelClass }}">Logo URL</span>
                                <input name="logo_url" value="{{ old('logo_url', $values['logo_url'] ?? '') }}" class="{{ $inputClass }}">
                                @error('logo_url')
                                    <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                                @enderror
                            </label>

                            <div class="rounded-2xl border border-slate-200/70 p-5 dark:border-gray-800">
                                <input type="hidden" name="announcement_enabled" value="0">

                                <label class="inline-flex items-center gap-3 text-sm font-medium text-slate-700 dark:text-gray-300">
                                    <input
                                        type="checkbox"
                                        name="announcement_enabled"
                                        value="1"
                                        class="{{ $toggleClass }}"
                                        @checked(old('announcement_enabled', $values['announcement_enabled'] ?? false))
                                    >
                                    Announcement enabled
                                </label>

                                <div class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-2">
                                    <label class="block">
                                        <span class="{{ $labelClass }}">Announcement text</span>
                                        <input name="announcement_text" value="{{ old('announcement_text', $values['announcement_text'] ?? '') }}" class="{{ $inputClass }}">
                                        @error('announcement_text')
                                            <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                                        @enderror
                                    </label>

                                    <label class="block">
                                        <span class="{{ $labelClass }}">Announcement link</span>
                                        <input name="announcement_link" value="{{ old('announcement_link', $values['announcement_link'] ?? '') }}" class="{{ $inputClass }}">
                                        @error('announcement_link')
                                            <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                                        @enderror
                                    </label>
                                </div>
                            </div>

                            <label class="block">
                                <span class="{{ $labelClass }}">Navigation menu selector</span>
                                <select name="menu_id" class="{{ $inputClass }}">
                                    <option value="">No menu selected</option>

                                    @foreach ($editor['menus'] as $menu)
                                        <option value="{{ $menu['id'] }}" @selected((string) old('menu_id', $values['menu_id'] ?? '') === (string) $menu['id'])>
                                            {{ $menu['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('menu_id')
                                    <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                                @enderror
                            </label>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                @foreach ([
                                    'show_search' => 'Show search',
                                    'show_account' => 'Show account icon',
                                    'show_cart' => 'Show cart icon',
                                    'sticky' => 'Sticky header',
                                ] as $field => $label)
                                    <input type="hidden" name="{{ $field }}" value="0">

                                    <label class="flex items-center gap-3 rounded-2xl border border-slate-200/70 p-4 text-sm font-medium text-slate-700 dark:border-gray-800 dark:text-gray-300">
                                        <input
                                            type="checkbox"
                                            name="{{ $field }}"
                                            value="1"
                                            class="{{ $toggleClass }}"
                                            @checked(old($field, $values[$field] ?? false))
                                        >
                                        {{ $label }}
                                    </label>
                                @endforeach
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

                            <div class="rounded-2xl border border-slate-200/70 p-5 dark:border-gray-800">
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
                        <div class="rounded-2xl border border-slate-200/70 bg-slate-50 p-6 dark:border-gray-800 dark:bg-gray-950">
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
                    @endif
                </main>

                <aside class="min-w-0 bg-slate-50/70 p-6 dark:bg-gray-950/40">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="{{ $panelTitleClass }}">
                            {{ $preview['title'] }}
                        </h2>

                        @if (in_array($preview['type'], ['header', 'footer'], true))
                            <div class="flex rounded-xl border border-slate-200 bg-white p-1 dark:border-gray-800 dark:bg-gray-900" data-preview-tabs>
                                <button type="button" class="rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-600 dark:bg-blue-950/40 dark:text-blue-300" data-preview-tab="desktop">Desktop</button>
                                <button type="button" class="rounded-lg px-3 py-1.5 text-xs font-medium text-slate-500 dark:text-gray-400" data-preview-tab="mobile">Mobile</button>
                            </div>
                        @endif
                    </div>

                    @if ($preview['type'] === 'header')
                        @php
                            $previewValues = $preview['values'];
                            $previewValues['logo_url'] = old('logo_url', $previewValues['logo_url'] ?? null);
                            $previewValues['announcement_enabled'] = old('announcement_enabled', $previewValues['announcement_enabled'] ?? false);
                            $previewValues['announcement_text'] = old('announcement_text', $previewValues['announcement_text'] ?? null);
                            $previewValues['show_search'] = old('show_search', $previewValues['show_search'] ?? true);
                            $previewValues['show_account'] = old('show_account', $previewValues['show_account'] ?? true);
                            $previewValues['show_cart'] = old('show_cart', $previewValues['show_cart'] ?? true);
                        @endphp

                        <div class="mt-5 space-y-4" data-preview-root>
                            <div class="rounded-[24px] border border-slate-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900" data-preview-panel="desktop">
                                @if ($previewValues['announcement_enabled'])
                                    <div class="rounded-xl bg-slate-950 px-4 py-2 text-center text-xs font-medium text-white">
                                        {{ $previewValues['announcement_text'] ?: 'Announcement message' }}
                                    </div>
                                @endif

                                <div class="mt-4 flex items-center justify-between gap-4 rounded-2xl border border-slate-200 p-4 dark:border-gray-800">
                                    <div class="min-w-0">
                                        @if ($previewValues['logo_url'])
                                            <img src="{{ $previewValues['logo_url'] }}" alt="" class="h-8 max-w-32 object-contain">
                                        @else
                                            <div class="font-sans text-base font-semibold text-slate-950 dark:text-white">Storefront</div>
                                        @endif
                                    </div>

                                    <div class="hidden min-w-0 flex-1 items-center justify-center gap-4 xl:flex">
                                        @foreach ($preview['navigation_labels'] as $label)
                                            <span class="text-sm font-medium text-slate-600 dark:text-gray-300">{{ $label }}</span>
                                        @endforeach
                                    </div>

                                    <div class="flex items-center gap-2">
                                        @if ($previewValues['show_search'])
                                            <span class="rounded-full border border-slate-200 px-3 py-1 text-xs text-slate-500 dark:border-gray-800 dark:text-gray-400">Search</span>
                                        @endif

                                        @if ($previewValues['show_account'])
                                            <span class="h-8 w-8 rounded-full bg-slate-100 dark:bg-gray-800"></span>
                                        @endif

                                        @if ($previewValues['show_cart'])
                                            <span class="h-8 w-8 rounded-full bg-blue-50 dark:bg-blue-950/40"></span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="hidden rounded-[24px] border border-slate-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900" data-preview-panel="mobile">
                                @if ($previewValues['announcement_enabled'])
                                    <div class="rounded-xl bg-slate-950 px-4 py-2 text-center text-xs font-medium text-white">
                                        {{ $previewValues['announcement_text'] ?: 'Announcement message' }}
                                    </div>
                                @endif

                                <div class="mt-4 flex items-center justify-between rounded-2xl border border-slate-200 p-4 dark:border-gray-800">
                                    <span class="flex h-9 w-9 flex-col items-center justify-center gap-1 rounded-full bg-slate-100 dark:bg-gray-800">
                                        <span class="h-0.5 w-4 rounded-full bg-slate-500 dark:bg-gray-400"></span>
                                        <span class="h-0.5 w-4 rounded-full bg-slate-500 dark:bg-gray-400"></span>
                                        <span class="h-0.5 w-4 rounded-full bg-slate-500 dark:bg-gray-400"></span>
                                    </span>

                                    <span class="font-sans text-base font-semibold text-slate-950 dark:text-white">Storefront</span>

                                    @if ($previewValues['show_cart'])
                                        <span class="h-9 w-9 rounded-full bg-blue-50 dark:bg-blue-950/40"></span>
                                    @else
                                        <span class="h-9 w-9"></span>
                                    @endif
                                </div>
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

                        <div class="mt-5 space-y-4" data-preview-root>
                            <div class="rounded-[24px] border border-slate-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900" data-preview-panel="desktop">
                                <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
                                    <div>
                                        <p class="font-sans text-base font-semibold text-slate-950 dark:text-white">Storefront</p>
                                        <div class="mt-4 space-y-2 text-sm text-slate-500 dark:text-gray-400">
                                            <p>{{ $previewValues['contact_email'] ?: 'hello@example.com' }}</p>
                                            <p>{{ $previewValues['contact_phone'] ?: '+1 555 0100' }}</p>
                                        </div>
                                    </div>

                                    @if ($previewValues['newsletter_enabled'])
                                        <div class="rounded-2xl bg-slate-50 p-4 dark:bg-gray-950">
                                            <p class="font-medium text-slate-900 dark:text-white">{{ $previewValues['newsletter_heading'] ?: 'Join our newsletter' }}</p>
                                            <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">{{ $previewValues['newsletter_text'] ?: 'Get updates and offers from the store.' }}</p>
                                            <div class="mt-4 h-9 rounded-xl bg-white dark:bg-gray-900"></div>
                                        </div>
                                    @endif
                                </div>

                                <div class="mt-6 grid grid-cols-3 gap-4">
                                    @for ($column = 0; $column < 3; $column++)
                                        <div class="space-y-2">
                                            <div class="h-2 w-16 rounded-full bg-slate-300 dark:bg-gray-700"></div>
                                            <div class="h-2 rounded-full bg-slate-200 dark:bg-gray-800"></div>
                                            <div class="h-2 w-4/5 rounded-full bg-slate-200 dark:bg-gray-800"></div>
                                        </div>
                                    @endfor
                                </div>

                                <p class="mt-6 border-t border-slate-200 pt-4 text-xs text-slate-500 dark:border-gray-800 dark:text-gray-400">
                                    {{ $previewValues['copyright_text'] ?: 'Copyright Storefront. All rights reserved.' }}
                                </p>
                            </div>

                            <div class="hidden rounded-[24px] border border-slate-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900" data-preview-panel="mobile">
                                <p class="font-sans text-base font-semibold text-slate-950 dark:text-white">Storefront</p>

                                @if ($previewValues['newsletter_enabled'])
                                    <div class="mt-4 rounded-2xl bg-slate-50 p-4 dark:bg-gray-950">
                                        <p class="font-medium text-slate-900 dark:text-white">{{ $previewValues['newsletter_heading'] ?: 'Join our newsletter' }}</p>
                                        <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">{{ $previewValues['newsletter_text'] ?: 'Get updates and offers from the store.' }}</p>
                                    </div>
                                @endif

                                <div class="mt-5 space-y-2 text-sm text-slate-500 dark:text-gray-400">
                                    <p>{{ $previewValues['contact_email'] ?: 'hello@example.com' }}</p>
                                    <p>{{ $previewValues['contact_phone'] ?: '+1 555 0100' }}</p>
                                </div>

                                <p class="mt-5 border-t border-slate-200 pt-4 text-xs text-slate-500 dark:border-gray-800 dark:text-gray-400">
                                    {{ $previewValues['copyright_text'] ?: 'Copyright Storefront. All rights reserved.' }}
                                </p>
                            </div>
                        </div>
                    @else
                        <div class="mt-5 rounded-[24px] border border-slate-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                            <p class="{{ $bodyTextClass }}">
                                {{ $preview['description'] }}
                            </p>
                        </div>
                    @endif
                </aside>
            </div>
        </section>
    </div>

    <script>
        document.querySelectorAll('[data-preview-root]').forEach((root) => {
            const wrapper = root.closest('aside');
            const tabs = wrapper?.querySelectorAll('[data-preview-tab]') ?? [];
            const panels = root.querySelectorAll('[data-preview-panel]');

            tabs.forEach((tab) => {
                tab.addEventListener('click', () => {
                    const activeMode = tab.dataset.previewTab;

                    tabs.forEach((button) => {
                        button.classList.toggle('bg-blue-50', button.dataset.previewTab === activeMode);
                        button.classList.toggle('text-blue-600', button.dataset.previewTab === activeMode);
                        button.classList.toggle('dark:bg-blue-950/40', button.dataset.previewTab === activeMode);
                        button.classList.toggle('dark:text-blue-300', button.dataset.previewTab === activeMode);
                        button.classList.toggle('text-slate-500', button.dataset.previewTab !== activeMode);
                        button.classList.toggle('dark:text-gray-400', button.dataset.previewTab !== activeMode);
                    });

                    panels.forEach((panel) => {
                        panel.classList.toggle('hidden', panel.dataset.previewPanel !== activeMode);
                    });
                });
            });
        });
    </script>
</x-admin::layouts>
