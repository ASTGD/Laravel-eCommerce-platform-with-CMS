<x-admin::layouts>
    <x-slot:title>Theme Presets</x-slot:title>

    @php
        $defaultTokens = data_get($defaultPreset?->tokens_json, 'colors', []);
        $defaultSettings = $defaultPreset?->settings_json ?? [];

        $stats = [
            [
                'label' => 'Total presets',
                'value' => $presets->count(),
                'help' => 'Theme combinations available in this workspace',
            ],
            [
                'label' => 'Active presets',
                'value' => $activePresetCount,
                'help' => 'Presets currently eligible for storefront use',
            ],
            [
                'label' => 'Token sets',
                'value' => $tokenSetCount,
                'help' => 'Structured token packages synced from themes',
            ],
            [
                'label' => 'Product variants',
                'value' => $variantCount,
                'help' => 'Unique product card variants configured',
            ],
        ];

        $palette = [
            'background' => $defaultTokens['background'] ?? '#f8fafc',
            'surface' => $defaultTokens['surface'] ?? '#ffffff',
            'primary' => $defaultTokens['primary'] ?? '#0f172a',
            'accent' => $defaultTokens['accent'] ?? '#ea580c',
            'text' => $defaultTokens['text'] ?? '#0f172a',
            'muted' => $defaultTokens['muted'] ?? '#64748b',
        ];
    @endphp

    <div class="space-y-6 pb-8">
        <section class="relative overflow-hidden rounded-[2rem] border border-slate-200 bg-slate-950 px-6 py-6 text-white shadow-2xl shadow-slate-950/20 lg:px-8 lg:py-8 dark:border-slate-800">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(251,146,60,0.22),_transparent_40%),radial-gradient(circle_at_bottom_left,_rgba(59,130,246,0.16),_transparent_35%)]"></div>

            <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl space-y-4">
                    <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-4 py-2 text-xs font-semibold uppercase tracking-[0.28em] text-slate-200">
                        Admin theme control
                    </div>

                    <div class="space-y-3">
                        <h1 class="text-3xl font-semibold tracking-tight text-white md:text-4xl">
                            Theme Presets
                        </h1>

                        <p class="max-w-2xl text-sm leading-6 text-slate-300 md:text-base">
                            Manage storefront visual presets from a TailPanel-inspired admin screen without changing the underlying theme behavior.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <a
                            href="{{ route('admin.theme.presets.create') }}"
                            class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-slate-100"
                        >
                            New preset
                        </a>

                        <a
                            href="#preset-library"
                            class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/10"
                        >
                            View library
                        </a>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 xl:w-[30rem]">
                    <article class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-300">Default preset</p>
                        <p class="mt-2 text-lg font-semibold text-white">
                            {{ $defaultPreset?->name ?? 'None configured' }}
                        </p>
                        <p class="mt-1 text-sm text-slate-300">
                            {{ $defaultPreset?->code ?? 'Create one to define the active storefront theme.' }}
                        </p>
                    </article>

                    <article class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-300">Last updated</p>
                        <p class="mt-2 text-lg font-semibold text-white">
                            {{ $defaultPreset?->updated_at?->diffForHumans() ?? 'No presets yet' }}
                        </p>
                        <p class="mt-1 text-sm text-slate-300">
                            Current surface, accent, and typography values stay tied to the selected preset.
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($stats as $stat)
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">
                                {{ $stat['label'] }}
                            </p>
                            <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-950 dark:text-white">
                                {{ $stat['value'] }}
                            </p>
                        </div>

                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-950 text-sm font-semibold text-white shadow-sm shadow-slate-900/20 dark:bg-white dark:text-slate-950">
                            {{ strtoupper(substr($stat['label'], 0, 1)) }}
                        </span>
                    </div>

                    <p class="mt-3 text-sm leading-6 text-slate-500 dark:text-slate-400">
                        {{ $stat['help'] }}
                    </p>
                </article>
            @endforeach
        </section>

        <section class="space-y-6">
            <article id="preset-library" class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm shadow-slate-200/60 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                <div class="flex flex-col gap-4 border-b border-slate-200 px-6 py-6 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">
                            Preset library
                        </p>
                        <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950 dark:text-white">
                            Visual library
                        </h2>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500 dark:text-slate-400">
                            Each row shows the underlying theme palette, active state, and configured storefront variant.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <a
                            href="{{ route('admin.theme.presets.create') }}"
                            class="inline-flex items-center rounded-full bg-slate-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-100"
                        >
                            Create preset
                        </a>

                        <span class="inline-flex items-center rounded-full border border-slate-200 px-4 py-2 text-sm font-medium text-slate-500 dark:border-slate-700 dark:text-slate-300">
                            {{ $activePresetCount }} active
                        </span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full border-separate border-spacing-0 text-left">
                        <thead class="bg-slate-50/80 text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:bg-slate-950 dark:text-slate-400">
                            <tr>
                                <th class="px-6 py-4">Preset</th>
                                <th class="px-6 py-4">Palette</th>
                                <th class="px-6 py-4">Variants</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            @forelse ($presets as $preset)
                                @php
                                    $colors = data_get($preset->tokens_json, 'colors', []);
                                    $settings = $preset->settings_json ?? [];
                                    $productVariant = data_get($settings, 'product_card_variant', 'default');
                                    $headerVariant = data_get($settings, 'header_variant', 'standard');
                                    $footerVariant = data_get($settings, 'footer_variant', 'standard');
                                @endphp

                                <tr class="bg-white transition hover:bg-slate-50/80 dark:bg-slate-900 dark:hover:bg-slate-800/60">
                                    <td class="px-6 py-5">
                                        <div class="flex items-center gap-4">
                                            <div
                                                class="flex h-12 w-12 items-center justify-center rounded-2xl border border-slate-200 shadow-sm dark:border-slate-700"
                                                style="background-color: {{ $colors['surface'] ?? '#ffffff' }}"
                                            >
                                                <span
                                                    class="h-5 w-5 rounded-full border-2 border-white shadow-sm"
                                                    style="background-color: {{ $colors['accent'] ?? '#ea580c' }}"
                                                ></span>
                                            </div>

                                            <div>
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <p class="text-base font-semibold text-slate-950 dark:text-white">
                                                        {{ $preset->name }}
                                                    </p>

                                                    @if ($preset->is_default)
                                                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                                                            Default
                                                        </span>
                                                    @endif
                                                </div>

                                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                                    {{ $preset->code }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-6 py-5">
                                        <div class="flex flex-wrap gap-2">
                                            <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 px-3 py-1 text-xs font-medium text-slate-600 dark:border-slate-700 dark:text-slate-300">
                                                <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $colors['background'] ?? '#f8fafc' }}"></span>
                                                Background
                                            </span>

                                            <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 px-3 py-1 text-xs font-medium text-slate-600 dark:border-slate-700 dark:text-slate-300">
                                                <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $colors['primary'] ?? '#0f172a' }}"></span>
                                                Primary
                                            </span>

                                            <span class="inline-flex items-center gap-2 rounded-full border border-slate-200 px-3 py-1 text-xs font-medium text-slate-600 dark:border-slate-700 dark:text-slate-300">
                                                <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $colors['accent'] ?? '#ea580c' }}"></span>
                                                Accent
                                            </span>
                                        </div>
                                    </td>

                                    <td class="px-6 py-5">
                                        <div class="space-y-2 text-sm text-slate-600 dark:text-slate-300">
                                            <p>
                                                <span class="font-medium text-slate-950 dark:text-white">Header:</span>
                                                {{ $headerVariant }}
                                            </p>
                                            <p>
                                                <span class="font-medium text-slate-950 dark:text-white">Footer:</span>
                                                {{ $footerVariant }}
                                            </p>
                                            <p>
                                                <span class="font-medium text-slate-950 dark:text-white">Product:</span>
                                                {{ $productVariant }}
                                            </p>
                                        </div>
                                    </td>

                                    <td class="px-6 py-5">
                                        <div class="flex flex-wrap gap-2">
                                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] {{ $preset->is_active ? 'bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }}">
                                                {{ $preset->is_active ? 'Active' : 'Inactive' }}
                                            </span>

                                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                                {{ $preset->updated_at?->format('d M Y') ?? 'No date' }}
                                            </span>
                                        </div>
                                    </td>

                                    <td class="px-6 py-5 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a
                                                href="{{ route('admin.theme.presets.edit', $preset) }}"
                                                class="inline-flex items-center rounded-full border border-slate-200 px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
                                            >
                                                Edit
                                            </a>

                                            <form method="POST" action="{{ route('admin.theme.presets.destroy', $preset) }}">
                                                @csrf
                                                @method('DELETE')

                                                <button
                                                    type="submit"
                                                    onclick="return confirm('Delete this preset?')"
                                                    class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-3.5 py-2 text-sm font-semibold text-rose-700 transition hover:border-rose-300 hover:bg-rose-100 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-200"
                                                >
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-16 text-center">
                                        <div class="mx-auto max-w-md space-y-3">
                                            <p class="text-lg font-semibold text-slate-950 dark:text-white">
                                                No theme presets created yet.
                                            </p>

                                            <p class="text-sm leading-6 text-slate-500 dark:text-slate-400">
                                                Create the first preset to define the base colors, spacing, and storefront variants for this installation.
                                            </p>

                                            <a
                                                href="{{ route('admin.theme.presets.create') }}"
                                                class="inline-flex items-center rounded-full bg-slate-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 dark:bg-white dark:text-slate-950"
                                            >
                                                Create preset
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>

            <div class="grid gap-6 xl:grid-cols-2">
                <article class="overflow-hidden rounded-[2rem] border border-slate-200 bg-slate-950 text-white shadow-sm shadow-slate-200/60 dark:border-slate-800 dark:shadow-none">
                    <div class="border-b border-white/10 px-6 py-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-300">
                            Default preset preview
                        </p>
                        <h3 class="mt-2 text-2xl font-semibold tracking-tight">
                            {{ $defaultPreset?->name ?? 'No default preset' }}
                        </h3>
                        <p class="mt-2 text-sm leading-6 text-slate-300">
                            {{ $defaultPreset?->code ?? 'Pick a preset to preview the theme surfaces, accent color, and typography metadata.' }}
                        </p>
                    </div>

                    <div class="grid gap-3 px-6 py-6">
                        @foreach ($palette as $label => $color)
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
                            <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Typography</p>
                            <p class="mt-2 font-semibold text-white">
                                {{ data_get($defaultPreset?->tokens_json, 'typography.heading', 'Poppins') }}
                            </p>
                            <p class="text-slate-300">
                                Body {{ data_get($defaultPreset?->tokens_json, 'typography.body', 'Poppins') }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs uppercase tracking-[0.18em] text-slate-400">Spacing</p>
                            <p class="mt-2 font-semibold text-white">
                                {{ data_get($defaultPreset?->tokens_json, 'spacing.section', '4rem') }}
                            </p>
                            <p class="text-slate-300">
                                Grid {{ data_get($defaultPreset?->tokens_json, 'spacing.grid', '1.5rem') }}
                            </p>
                        </div>
                    </div>
                </article>

                <article class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">
                        Operating notes
                    </p>

                    <h3 class="mt-2 text-xl font-semibold tracking-tight text-slate-950 dark:text-white">
                        Keep the screen visual, not behavioral
                    </h3>

                    <div class="mt-4 space-y-3 text-sm leading-6 text-slate-600 dark:text-slate-300">
                        <p class="rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-800/60">
                            This page is intentionally styled like a dashboard so it feels closer to the demo without changing the preset workflow.
                        </p>
                        <p class="rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-800/60">
                            Use the same visual direction when you add more custom admin screens under the theme package.
                        </p>
                        <p class="rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-800/60">
                            Keep Bagisto commerce admin intact; only the custom theme screens should pick up this design language.
                        </p>
                    </div>
                </article>
            </div>
        </section>
    </div>
</x-admin::layouts>
