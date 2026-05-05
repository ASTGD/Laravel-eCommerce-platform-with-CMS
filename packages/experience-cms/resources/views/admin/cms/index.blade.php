@php
    $cardClass = 'rounded-[24px] border border-slate-200/70 bg-white p-6 shadow-none transition dark:border-gray-800 dark:bg-gray-900';
    $cardHoverClass = 'hover:border-blue-200 hover:bg-blue-50/40 dark:hover:border-blue-800 dark:hover:bg-blue-950/20';
    $cardTitleClass = 'font-sans text-lg leading-7 font-semibold tracking-normal text-slate-950 dark:text-white';
    $bodyTextClass = 'text-sm leading-6 text-slate-500 dark:text-gray-400';
@endphp

<x-admin::layouts>
    <x-slot:title>CMS</x-slot:title>

    <div class="space-y-6 pb-8">
        <section class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="font-sans text-2xl leading-8 font-bold tracking-tight text-slate-950 dark:text-white">
                    CMS
                </h1>

                <p class="mt-1 text-sm leading-6 text-slate-500 dark:text-gray-400">
                    Manage storefront pages, menus, layout content, templates, and reusable website sections.
                </p>
            </div>

            @if ($createPageUrl)
                <a
                    href="{{ $createPageUrl }}"
                    class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700"
                >
                    Create Page
                </a>
            @endif
        </section>

        @foreach ($cardGroups as $group)
            <section class="space-y-4">
                <h2 class="{{ $cardTitleClass }}">
                    {{ $group['title'] }}
                </h2>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($group['cards'] as $card)
                        <a
                            href="{{ $card['url'] }}"
                            class="{{ $cardClass }} {{ $cardHoverClass }}"
                        >
                            <span class="{{ $cardTitleClass }}">
                                {{ $card['title'] }}
                            </span>

                            <span class="mt-2 block {{ $bodyTextClass }}">
                                {{ $card['description'] }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</x-admin::layouts>
