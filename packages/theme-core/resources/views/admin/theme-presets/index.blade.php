@php
    $titleClass = 'font-sans text-2xl leading-8 font-bold tracking-tight text-slate-950 dark:text-white';
    $subtitleClass = 'mt-1 text-sm leading-6 text-slate-500 dark:text-gray-400';
    $cardClass = 'rounded-[24px] border border-slate-200/70 bg-white p-6 shadow-none dark:border-gray-800 dark:bg-gray-900';
    $cardTitleClass = 'font-sans text-lg leading-7 font-semibold tracking-normal text-slate-950 dark:text-white';
    $bodyTextClass = 'text-sm leading-6 text-slate-500 dark:text-gray-400';
    $metricValueClass = 'font-sans text-2xl leading-8 font-bold tracking-tight text-slate-950 dark:text-white';
    $primaryButtonClass = 'inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700';
    $secondaryButtonClass = 'inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-blue-700 dark:hover:bg-blue-950/40 dark:hover:text-blue-300';
    $badgeBaseClass = 'inline-flex items-center rounded-full px-3 py-1 text-xs font-medium';
    $activeBadgeClass = 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300';
    $inactiveBadgeClass = 'bg-slate-100 text-slate-600 dark:bg-gray-800 dark:text-gray-300';
    $warningBadgeClass = 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300';

    $activePresetName = $activePreset?->name ?? 'Not Selected';
    $activePresetCode = $activePreset?->code;
    $canCreate = $themeActions['create'] && bouncer()->hasPermission('theme.presets.create');
    $canEdit = $themeActions['edit_route_exists'] && bouncer()->hasPermission('theme.presets.edit');
    $canDelete = $themeActions['delete_route_exists'] && bouncer()->hasPermission('theme.presets.delete');
    $canActivate = $themeActions['activate_route'] && bouncer()->hasPermission('theme.presets.edit');

    $readinessItems = [
        [
            'label' => 'Active preset selected',
            'ready' => (bool) $activePreset,
        ],
    ];
@endphp

<x-admin::layouts>
    <x-slot:title>Themes</x-slot:title>

    <div class="space-y-6 pb-8">
        <section class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="{{ $titleClass }}">
                    Themes
                </h1>

                <p class="{{ $subtitleClass }}">
                    Select, preview, and manage storefront theme presets.
                </p>
            </div>

            @if ($canCreate)
                <a
                    href="{{ $themeActions['create'] }}"
                    class="{{ $primaryButtonClass }}"
                >
                    Create Theme Preset
                </a>
            @endif
        </section>

        <section class="{{ $cardClass }}">
            <div class="flex flex-col gap-6 xl:flex-row xl:items-center xl:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-3">
                        <h2 class="{{ $cardTitleClass }}">
                            Active Theme
                        </h2>

                        <span class="{{ $badgeBaseClass }} {{ $activePreset ? $activeBadgeClass : $warningBadgeClass }}">
                            {{ $activePreset ? 'Active' : 'Not Selected' }}
                        </span>
                    </div>

                    <p class="mt-5 break-words {{ $metricValueClass }}">
                        {{ $activePresetName }}
                    </p>

                    <p class="mt-1 {{ $bodyTextClass }}">
                        Current storefront theme preset used by the website.
                    </p>

                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        @if ($activePresetCode)
                            <span class="{{ $badgeBaseClass }} {{ $inactiveBadgeClass }}">
                                {{ $activePresetCode }}
                            </span>
                        @endif

                        <span class="{{ $bodyTextClass }}">
                            {{ $activePreset?->updated_at?->diffForHumans() ?? 'No active preset selected' }}
                        </span>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a
                        href="{{ $themeActions['preview'] }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="{{ $secondaryButtonClass }}"
                    >
                        Preview Storefront
                    </a>

                    @if ($themeActions['index'])
                        <a
                            href="{{ $themeActions['index'] }}"
                            class="{{ $secondaryButtonClass }}"
                        >
                            Manage Theme Presets
                        </a>
                    @endif
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(320px,0.8fr)]">
            <div class="space-y-4">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="{{ $cardTitleClass }}">
                            Theme Presets
                        </h2>

                        <p class="{{ $bodyTextClass }}">
                            Review available storefront presets and manage their settings.
                        </p>
                    </div>

                    <span class="{{ $bodyTextClass }}">
                        {{ $presets->count() }} {{ \Illuminate\Support\Str::plural('preset', $presets->count()) }}
                    </span>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                    @forelse ($presets as $preset)
                        @php
                            $colors = data_get($preset->tokens_json, 'colors', []);
                            $description = ($themePresetColumns['description'] ?? false)
                                ? $preset->description
                                : null;
                            $isPresetActive = (string) $preset->getKey() === (string) $activePresetId;
                            $statusLabel = $isPresetActive ? 'Active' : ($preset->is_active ? 'Inactive' : 'Draft');
                        @endphp

                        <article class="{{ $cardClass }} flex min-h-full flex-col">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <h3 class="{{ $cardTitleClass }} break-words">
                                        {{ $preset->name ?? 'Untitled Theme' }}
                                    </h3>

                                    <p class="mt-1 break-all font-mono text-sm leading-6 text-slate-500 dark:text-gray-400">
                                        {{ $preset->code ?? 'no-code' }}
                                    </p>
                                </div>

                                <span class="{{ $badgeBaseClass }} shrink-0 {{ $isPresetActive ? $activeBadgeClass : $inactiveBadgeClass }}">
                                    {{ $statusLabel }}
                                </span>
                            </div>

                            <p class="mt-4 {{ $bodyTextClass }}">
                                {{ $description ?: 'Theme preset for storefront visual settings.' }}
                            </p>

                            <div class="mt-5 flex flex-wrap gap-2">
                                @foreach (['background', 'surface', 'primary', 'accent'] as $colorKey)
                                    <span
                                        class="h-8 w-8 rounded-full border border-slate-200 dark:border-gray-700"
                                        style="background-color: {{ data_get($colors, $colorKey, '#f8fafc') }}"
                                        title="{{ $colorKey }}"
                                    ></span>
                                @endforeach
                            </div>

                            <div class="mt-5 grid gap-2 text-sm leading-6 text-slate-500 dark:text-gray-400">
                                <div class="flex items-center justify-between gap-3">
                                    <span>Header</span>
                                    <span class="font-medium text-slate-950 dark:text-white">
                                        {{ data_get($preset->settings_json, 'header_variant', 'standard') }}
                                    </span>
                                </div>

                                <div class="flex items-center justify-between gap-3">
                                    <span>Footer</span>
                                    <span class="font-medium text-slate-950 dark:text-white">
                                        {{ data_get($preset->settings_json, 'footer_variant', 'standard') }}
                                    </span>
                                </div>

                                <div class="flex items-center justify-between gap-3">
                                    <span>Product</span>
                                    <span class="font-medium text-slate-950 dark:text-white">
                                        {{ data_get($preset->settings_json, 'product_card_variant', 'default') }}
                                    </span>
                                </div>
                            </div>

                            <div class="mt-auto flex flex-wrap items-center gap-3 pt-6">
                                @if ($canEdit)
                                    <a
                                        href="{{ route('admin.theme.presets.edit', $preset) }}"
                                        class="{{ $secondaryButtonClass }}"
                                    >
                                        Edit
                                    </a>
                                @endif

                                @if ($canActivate && ! $isPresetActive)
                                    <form method="POST" action="{{ route('admin.theme.presets.set-active', $preset->id) }}">
                                        @csrf

                                        <button
                                            type="submit"
                                            class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-blue-700"
                                        >
                                            Set Active
                                        </button>
                                    </form>
                                @endif

                                @if ($canDelete)
                                    <form method="POST" action="{{ route('admin.theme.presets.destroy', $preset) }}">
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            onclick="return confirm('Delete this preset?')"
                                            class="inline-flex items-center justify-center rounded-xl border border-rose-200 bg-white px-4 py-2 text-sm font-medium text-rose-700 transition hover:border-rose-300 hover:bg-rose-50 dark:border-rose-900/60 dark:bg-gray-900 dark:text-rose-300 dark:hover:bg-rose-950/20"
                                        >
                                            Delete
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </article>
                    @empty
                        <article class="{{ $cardClass }} md:col-span-2 xl:col-span-3">
                            <h3 class="{{ $cardTitleClass }}">
                                No theme presets created yet.
                            </h3>

                            <p class="mt-2 {{ $bodyTextClass }}">
                                Create the first preset to define storefront colors, spacing, typography, and layout variants.
                            </p>

                            @if ($canCreate)
                                <a
                                    href="{{ $themeActions['create'] }}"
                                    class="mt-5 {{ $primaryButtonClass }}"
                                >
                                    Create Theme Preset
                                </a>
                            @endif
                        </article>
                    @endforelse
                </div>
            </div>

            <aside class="space-y-6">
                <section class="{{ $cardClass }}">
                    <h2 class="{{ $cardTitleClass }}">
                        Theme Readiness
                    </h2>

                    <div class="mt-4 space-y-3">
                        @foreach ($readinessItems as $item)
                            <div class="flex items-center justify-between gap-4 border-b border-slate-100 py-3 last:border-b-0 dark:border-gray-800">
                                <span class="{{ $bodyTextClass }}">
                                    {{ $item['label'] }}
                                </span>

                                <span class="{{ $badgeBaseClass }} {{ $item['ready'] ? $activeBadgeClass : $warningBadgeClass }}">
                                    {{ $item['ready'] ? 'Ready' : 'Needs Setup' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="{{ $cardClass }}">
                    <h2 class="{{ $cardTitleClass }}">
                        Quick Actions
                    </h2>

                    <div class="mt-4 grid gap-3">
                        <a
                            href="{{ $themeActions['preview'] }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="{{ $secondaryButtonClass }}"
                        >
                            Preview Storefront
                        </a>

                        @if ($canCreate)
                            <a
                                href="{{ $themeActions['create'] }}"
                                class="{{ $secondaryButtonClass }}"
                            >
                                Create Theme Preset
                            </a>
                        @endif
                    </div>

                </section>
            </aside>
        </section>
    </div>
</x-admin::layouts>
