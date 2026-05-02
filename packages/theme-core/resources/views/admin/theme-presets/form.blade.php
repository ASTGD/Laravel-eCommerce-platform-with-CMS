<x-admin::layouts>
    <x-slot:title>{{ $preset->exists ? 'Edit Theme Preset' : 'Create Theme Preset' }}</x-slot:title>

    @php
        $tokens = $preset->tokens_json ?? [];
        $settings = $preset->settings_json ?? [];

        $colors = data_get($tokens, 'colors', []);
        $typography = data_get($tokens, 'typography', []);
        $spacing = data_get($tokens, 'spacing', []);
        $radius = data_get($tokens, 'radius', []);
        $headerVariant = data_get($settings, 'header_variant', 'standard');
        $footerVariant = data_get($settings, 'footer_variant', 'standard');
        $productVariant = data_get($settings, 'product_card_variant', 'default');

        $defaultTokens = [
            'background' => $colors['background'] ?? '#f8fafc',
            'surface' => $colors['surface'] ?? '#ffffff',
            'primary' => $colors['primary'] ?? '#0f172a',
            'accent' => $colors['accent'] ?? '#ea580c',
            'text' => $colors['text'] ?? '#0f172a',
            'muted' => $colors['muted'] ?? '#64748b',
        ];
    @endphp

    <div class="space-y-6 pb-8">
        <section class="relative overflow-hidden rounded-[2rem] border border-slate-200 bg-white px-6 py-6 shadow-sm shadow-slate-200/60 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
            <div class="absolute inset-y-0 right-0 w-2/5 bg-[radial-gradient(circle_at_top_right,_rgba(234,88,12,0.16),_transparent_45%)]"></div>

            <div class="relative flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="max-w-3xl space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">
                        Theme preset editor
                    </p>

                    <h1 class="text-3xl font-semibold tracking-tight text-slate-950 dark:text-white md:text-4xl">
                        {{ $preset->exists ? 'Edit Theme Preset' : 'Create Theme Preset' }}
                    </h1>

                    <p class="max-w-2xl text-sm leading-6 text-slate-500 dark:text-slate-400">
                        Shape the storefront palette, typography, spacing, and variant settings from a single visual workspace.
                    </p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 xl:w-[30rem]">
                    <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">
                            Preset state
                        </p>
                        <p class="mt-2 text-lg font-semibold text-slate-950 dark:text-white">
                            {{ $preset->exists ? 'Editing existing preset' : 'New preset draft' }}
                        </p>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            {{ $preset->is_default ? 'Default preset currently enabled.' : 'Default switch is available in the form below.' }}
                        </p>
                    </article>

                    <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">
                            Visual hint
                        </p>
                        <p class="mt-2 text-lg font-semibold text-slate-950 dark:text-white">
                            {{ $productVariant }}
                        </p>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            Product card variant linked to this preset.
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.55fr)_minmax(320px,0.85fr)]">
            <form
                method="POST"
                action="{{ $preset->exists ? route('admin.theme.presets.update', $preset) : route('admin.theme.presets.store') }}"
                class="space-y-6"
            >
                @csrf
                @if ($preset->exists)
                    @method('PUT')
                @endif

                <article class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm shadow-slate-200/60 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                    <div class="border-b border-slate-200 px-6 py-6 dark:border-slate-800">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">
                            Preset identity
                        </p>
                        <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950 dark:text-white">
                            Name and code
                        </h2>
                    </div>

                    <div class="grid gap-6 px-6 py-6 md:grid-cols-2">
                        <label class="block">
                            <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">Name</span>
                            <input
                                name="name"
                                value="{{ old('name', $preset->name) }}"
                                class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-slate-950 focus:ring-2 focus:ring-slate-950/10 dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:focus:border-white dark:focus:ring-white/10"
                                placeholder="Modern"
                                required
                            >
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">Code</span>
                            <input
                                name="code"
                                value="{{ old('code', $preset->code) }}"
                                class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 font-mono text-sm text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-slate-950 focus:ring-2 focus:ring-slate-950/10 dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:focus:border-white dark:focus:ring-white/10"
                                placeholder="modern"
                            >
                        </label>
                    </div>
                </article>

                <article class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm shadow-slate-200/60 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                    <div class="border-b border-slate-200 px-6 py-6 dark:border-slate-800">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">
                            Theme tokens
                        </p>
                        <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950 dark:text-white">
                            Colors, spacing, and radius
                        </h2>
                    </div>

                    <div class="grid gap-6 px-6 py-6 lg:grid-cols-2">
                        <label class="block">
                            <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">Tokens JSON</span>
                            <textarea
                                name="tokens_json"
                                rows="18"
                                class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 font-mono text-sm text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-slate-950 focus:ring-2 focus:ring-slate-950/10 dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:focus:border-white dark:focus:ring-white/10"
                                spellcheck="false"
                            >{{ old('tokens_json', json_encode($preset->tokens_json ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) }}</textarea>
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-200">Settings JSON</span>
                            <textarea
                                name="settings_json"
                                rows="18"
                                class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 font-mono text-sm text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-slate-950 focus:ring-2 focus:ring-slate-950/10 dark:border-slate-700 dark:bg-slate-950 dark:text-white dark:focus:border-white dark:focus:ring-white/10"
                                spellcheck="false"
                            >{{ old('settings_json', json_encode($preset->settings_json ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) }}</textarea>
                        </label>
                    </div>
                </article>

                <article class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm shadow-slate-200/60 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                    <div class="border-b border-slate-200 px-6 py-6 dark:border-slate-800">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">
                            Publish controls
                        </p>
                        <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950 dark:text-white">
                            Visibility and default state
                        </h2>
                    </div>

                    <div class="space-y-6 px-6 py-6">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:gap-8">
                            <label class="inline-flex items-center gap-3 text-sm font-medium text-slate-700 dark:text-slate-200">
                                <input
                                    type="checkbox"
                                    name="is_default"
                                    value="1"
                                    {{ old('is_default', $preset->is_default) ? 'checked' : '' }}
                                    class="h-5 w-5 rounded border-slate-300 text-slate-950 focus:ring-slate-950 dark:border-slate-700 dark:bg-slate-950 dark:focus:ring-white/20"
                                >
                                Default preset
                            </label>

                            <label class="inline-flex items-center gap-3 text-sm font-medium text-slate-700 dark:text-slate-200">
                                <input
                                    type="checkbox"
                                    name="is_active"
                                    value="1"
                                    {{ old('is_active', $preset->exists ? $preset->is_active : true) ? 'checked' : '' }}
                                    class="h-5 w-5 rounded border-slate-300 text-slate-950 focus:ring-slate-950 dark:border-slate-700 dark:bg-slate-950 dark:focus:ring-white/20"
                                >
                                Active
                            </label>
                        </div>

                        <div class="flex flex-wrap items-center gap-3 border-t border-slate-200 pt-6 dark:border-slate-800">
                            <button
                                type="submit"
                                class="inline-flex items-center rounded-full bg-slate-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-100"
                            >
                                Save preset
                            </button>

                            <a
                                href="{{ route('admin.theme.presets.index') }}"
                                class="inline-flex items-center rounded-full border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
                            >
                                Cancel
                            </a>
                        </div>
                    </div>
                </article>
            </form>

            <aside class="space-y-6 xl:sticky xl:top-6">
                <article class="overflow-hidden rounded-[2rem] border border-slate-200 bg-slate-950 text-white shadow-sm shadow-slate-200/60 dark:border-slate-800 dark:shadow-none">
                    <div class="border-b border-white/10 px-6 py-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-300">
                            Live preview
                        </p>
                        <h3 class="mt-2 text-2xl font-semibold tracking-tight">
                            {{ old('name', $preset->name ?: 'Untitled preset') }}
                        </h3>
                        <p class="mt-2 text-sm leading-6 text-slate-300">
                            {{ old('code', $preset->code ?: 'generated-code') }}
                        </p>
                    </div>

                    <div class="grid gap-3 px-6 py-6">
                        @foreach ($defaultTokens as $label => $color)
                            <div class="flex items-center justify-between gap-4 rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="h-10 w-10 rounded-xl border border-white/15" style="background-color: {{ $color }}"></span>
                                    <div>
                                        <p class="text-sm font-semibold text-white">{{ ucfirst($label) }}</p>
                                        <p class="text-xs uppercase tracking-[0.18em] text-slate-300">{{ $color }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="grid grid-cols-2 gap-3 border-t border-white/10 px-6 py-6 text-sm">
                        <div>
                            <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Header</p>
                            <p class="mt-2 font-semibold text-white">{{ $headerVariant }}</p>
                        </div>

                        <div>
                            <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Footer</p>
                            <p class="mt-2 font-semibold text-white">{{ $footerVariant }}</p>
                        </div>

                        <div>
                            <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Product</p>
                            <p class="mt-2 font-semibold text-white">{{ $productVariant }}</p>
                        </div>

                        <div>
                            <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Radius</p>
                            <p class="mt-2 font-semibold text-white">
                                {{ data_get($radius, 'card', '1rem') }}
                            </p>
                        </div>
                    </div>
                </article>

                <article class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">
                        Notes
                    </p>

                    <h3 class="mt-2 text-xl font-semibold tracking-tight text-slate-950 dark:text-white">
                        What this screen controls
                    </h3>

                    <div class="mt-4 space-y-3 text-sm leading-6 text-slate-600 dark:text-slate-300">
                        <p class="rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-800/60">
                            The JSON inputs are still validated exactly as before. This is only a visual redesign of the preset editor.
                        </p>
                        <p class="rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-800/60">
                            The side preview is derived from the current preset data so you can see the palette before saving.
                        </p>
                        <p class="rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-800/60">
                            Use this same screen language for the rest of the custom admin package screens.
                        </p>
                    </div>
                </article>

                <article class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">
                        Current token hints
                    </p>

                    <div class="mt-4 space-y-4 text-sm text-slate-600 dark:text-slate-300">
                        <div>
                            <p class="font-medium text-slate-950 dark:text-white">Typography</p>
                            <p class="mt-1">
                                Heading: {{ data_get($typography, 'heading', 'Poppins') }} |
                                Body: {{ data_get($typography, 'body', 'Poppins') }}
                            </p>
                        </div>

                        <div>
                            <p class="font-medium text-slate-950 dark:text-white">Spacing</p>
                            <p class="mt-1">
                                Section: {{ data_get($spacing, 'section', '4rem') }} |
                                Grid: {{ data_get($spacing, 'grid', '1.5rem') }}
                            </p>
                        </div>
                    </div>
                </article>
            </aside>
        </div>
    </div>
</x-admin::layouts>
