@php
    $pageTitleClass = 'font-sans text-2xl leading-8 font-bold tracking-tight text-slate-950 dark:text-white';
    $pageSubtitleClass = 'mt-1 text-sm leading-6 text-slate-500 dark:text-gray-400';
    $cardClass = 'rounded-[24px] border border-slate-200/70 bg-white p-6 shadow-none dark:border-gray-700 dark:bg-gray-800';
    $cardTitleClass = 'font-sans text-lg leading-7 font-semibold tracking-normal text-slate-950 dark:text-white';
    $smallTextClass = 'text-sm leading-6 text-slate-500 dark:text-gray-400';
    $badgeBaseClass = 'inline-flex items-center rounded-full px-3 py-1 text-xs font-medium';
    $readyBadgeClass = 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300';
    $warningBadgeClass = 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300';
    $secondaryButtonClass = 'inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-blue-700 dark:hover:bg-blue-950/40 dark:hover:text-blue-300';
@endphp

<x-admin::layouts>
    <x-slot:title>Site Settings</x-slot:title>

    <div class="space-y-6 pb-8">
        <section class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="{{ $pageTitleClass }}">
                    Site Settings
                </h1>

                <p class="{{ $pageSubtitleClass }}">
                    Manage global website settings separately from CMS Studio content builders.
                </p>
            </div>

            <a
                href="{{ $previewStorefrontUrl }}"
                target="_blank"
                rel="noopener noreferrer"
                class="{{ $secondaryButtonClass }}"
            >
                Preview Storefront
            </a>
        </section>

        <section class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
            <div class="{{ $cardClass }}">
                <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="{{ $cardTitleClass }}">
                            Website Settings
                        </h2>

                        <p class="mt-1 {{ $smallTextClass }}">
                            These settings are available to the storefront runtime and theme surfaces without exposing raw JSON editing.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                    @foreach ($groups as $group)
                        <article class="rounded-2xl border border-slate-200/70 bg-slate-50 p-5 dark:border-gray-700 dark:bg-gray-950">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="font-sans text-base font-semibold text-slate-950 dark:text-white">
                                        {{ $group['name'] }}
                                    </h3>

                                    <p class="mt-2 {{ $smallTextClass }}">
                                        {{ $group['description'] }}
                                    </p>
                                </div>

                                <span class="{{ $badgeBaseClass }} {{ $group['status'] === 'Configured' ? $readyBadgeClass : $warningBadgeClass }}">
                                    {{ $group['status'] }}
                                </span>
                            </div>

                            <div class="mt-5 flex flex-wrap gap-2">
                                @foreach ($group['keys'] as $key)
                                    <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-500 dark:bg-gray-800 dark:text-gray-400">
                                        {{ $key }}
                                    </span>
                                @endforeach
                            </div>

                            <p class="mt-4 text-xs font-medium text-slate-500 dark:text-gray-400">
                                {{ $group['configured_fields'] }} configured {{ \Illuminate\Support\Str::plural('field', $group['configured_fields']) }}
                            </p>
                        </article>
                    @endforeach
                </div>
            </div>

            <aside class="{{ $cardClass }}">
                <h2 class="{{ $cardTitleClass }}">
                    Status
                </h2>

                <div class="mt-5 space-y-4">
                    <div class="rounded-2xl border border-slate-200/70 p-4 dark:border-gray-700">
                        <p class="text-xs font-medium text-slate-500 dark:text-gray-400">
                            Total Settings
                        </p>

                        <p class="mt-2 font-sans text-2xl font-semibold text-slate-950 dark:text-white">
                            {{ $summary['total'] ?? 0 }}
                        </p>
                    </div>

                    <div class="rounded-2xl border border-slate-200/70 p-4 dark:border-gray-700">
                        <p class="text-xs font-medium text-slate-500 dark:text-gray-400">
                            Configured Groups
                        </p>

                        <div class="mt-3 flex flex-wrap gap-2">
                            @forelse ($summary['groups'] ?? [] as $group)
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600 dark:bg-gray-700 dark:text-gray-300">
                                    {{ $group['name'] }} · {{ $group['count'] }}
                                </span>
                            @empty
                                <span class="text-sm text-slate-500 dark:text-gray-400">
                                    No settings configured yet.
                                </span>
                            @endforelse
                        </div>
                    </div>
                </div>
            </aside>
        </section>
    </div>
</x-admin::layouts>
