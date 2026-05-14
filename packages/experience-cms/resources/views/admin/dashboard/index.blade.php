@php
    $cardClass = 'rounded-[24px] border border-slate-200/70 bg-white p-6 shadow-none dark:border-gray-700 dark:bg-gray-800';
    $cardTitleClass = 'font-sans text-lg leading-7 font-semibold tracking-normal text-slate-950 dark:text-white';
    $smallLabelClass = 'text-sm leading-6 text-slate-500 dark:text-gray-400';
    $metricValueClass = 'font-sans text-2xl leading-8 font-bold tracking-tight text-slate-950 dark:text-white';
    $badgeBaseClass = 'inline-flex items-center rounded-full px-3 py-1 text-xs font-medium';
    $readyBadgeClass = 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300';
    $warningBadgeClass = 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300';
    $neutralBadgeClass = 'bg-slate-100 text-slate-600 dark:bg-gray-800 dark:text-gray-300';
    $blueBadgeClass = 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300';

    $themeName = $overview['active_theme']['name'] ?? 'Not Selected';
    $themeCode = $overview['active_theme']['code'] ?? null;
    $isSetupComplete = $overview['setup']['is_complete'];

    $homepageStatus = match ($overview['homepage']['status']) {
        'published' => ['label' => 'Published', 'class' => $readyBadgeClass],
        'draft' => ['label' => 'Draft', 'class' => $warningBadgeClass],
        default => ['label' => 'Missing', 'class' => $warningBadgeClass],
    };

    $setupRows = [
        [
            'label' => 'Homepage',
            'value' => $overview['homepage']['label'],
            'badge' => $homepageStatus['label'],
            'class' => $homepageStatus['class'],
        ],
        [
            'label' => 'Active Theme',
            'value' => $themeName,
            'badge' => $overview['setup']['has_theme'] ? 'Configured' : 'Missing',
            'class' => $overview['setup']['has_theme'] ? $readyBadgeClass : $warningBadgeClass,
        ],
        [
            'label' => 'Header',
            'value' => $overview['setup']['has_header'] ? 'Configured' : 'Missing',
            'badge' => $overview['setup']['has_header'] ? 'Ready' : 'Needs Setup',
            'class' => $overview['setup']['has_header'] ? $readyBadgeClass : $warningBadgeClass,
        ],
        [
            'label' => 'Footer',
            'value' => $overview['setup']['has_footer'] ? 'Configured' : 'Missing',
            'badge' => $overview['setup']['has_footer'] ? 'Ready' : 'Needs Setup',
            'class' => $overview['setup']['has_footer'] ? $readyBadgeClass : $warningBadgeClass,
        ],
        [
            'label' => 'Navigation Menu',
            'value' => $overview['setup']['has_menu'] ? 'Configured' : 'Missing',
            'badge' => $overview['setup']['has_menu'] ? 'Ready' : 'Needs Setup',
            'class' => $overview['setup']['has_menu'] ? $readyBadgeClass : $warningBadgeClass,
        ],
        [
            'label' => 'Site Settings',
            'value' => $overview['setup']['has_site_settings'] ? 'Configured' : 'Missing',
            'badge' => $overview['setup']['has_site_settings'] ? 'Ready' : 'Needs Setup',
            'class' => $overview['setup']['has_site_settings'] ? $readyBadgeClass : $warningBadgeClass,
        ],
    ];

    $contentRows = [
        ['label' => 'Menus', 'value' => $overview['menus']],
        ['label' => 'Header Builders', 'value' => $overview['header_configs']],
        ['label' => 'Footer Builders', 'value' => $overview['footer_configs']],
        ['label' => 'Site Settings', 'value' => $overview['site_settings']],
    ];

    $quickActions = [
        [
            'title' => 'CMS Studio',
            'description' => 'Manage safe website content and layout areas.',
            'url' => $urls['cms'],
        ],
        [
            'title' => 'Manage Themes',
            'description' => 'Select and configure website themes.',
            'url' => $urls['themes'],
        ],
        [
            'title' => 'Website Settings',
            'description' => 'Update global website configuration.',
            'url' => $urls['settings'],
        ],
        [
            'title' => 'Header Builder',
            'description' => 'Edit storefront logo, menu, and header controls.',
            'url' => $urls['header'],
        ],
        [
            'title' => 'Footer Builder',
            'description' => 'Edit newsletter, contact, social, and copyright content.',
            'url' => $urls['footer'],
        ],
    ];
@endphp

<x-admin::layouts>
    <x-slot:title>My Website</x-slot:title>

    <div class="space-y-6 pb-8">
        <section class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="space-y-2">
                <h1 class="font-sans text-3xl font-semibold tracking-normal text-slate-950 dark:text-white">
                    My Website
                </h1>

                <p class="{{ $smallLabelClass }}">
                    Manage storefront content, theme, layout, and website settings.
                </p>
            </div>

            <a
                href="{{ $urls['preview'] }}"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-blue-700 dark:hover:bg-blue-950/40 dark:hover:text-blue-300"
            >
                Preview Storefront
            </a>
        </section>

        <section class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
            <article class="{{ $cardClass }}">
                <div class="flex items-start justify-between gap-4">
                    <h2 class="{{ $cardTitleClass }}">
                        Public Site
                    </h2>

                    <span class="{{ $badgeBaseClass }} {{ $blueBadgeClass }}">
                        {{ $overview['public_site']['label'] }}
                    </span>
                </div>

                <p class="mt-5 {{ $metricValueClass }}">
                    {{ $overview['public_site']['label'] }}
                </p>

                <p class="mt-1 {{ $smallLabelClass }}">
                    {{ $overview['public_site']['description'] }}
                </p>
            </article>

            <article class="{{ $cardClass }}">
                <div class="flex items-start justify-between gap-4">
                    <h2 class="{{ $cardTitleClass }}">
                        Active Theme
                    </h2>

                    <span class="{{ $badgeBaseClass }} {{ $themeCode ? $neutralBadgeClass : $warningBadgeClass }}">
                        {{ $themeCode ?? 'Missing' }}
                    </span>
                </div>

                <p class="mt-5 break-words {{ $metricValueClass }}">
                    {{ $themeName }}
                </p>

                <p class="mt-1 {{ $smallLabelClass }}">
                    Current storefront theme preset
                </p>
            </article>

            <article class="{{ $cardClass }}">
                <div class="flex items-start justify-between gap-4">
                    <h2 class="{{ $cardTitleClass }}">
                        Homepage
                    </h2>

                    <span class="{{ $badgeBaseClass }} {{ $homepageStatus['class'] }}">
                        {{ $homepageStatus['label'] }}
                    </span>
                </div>

                <p class="mt-5 break-words {{ $metricValueClass }}">
                    {{ $overview['homepage']['label'] }}
                </p>

                <p class="mt-1 {{ $smallLabelClass }}">
                    Hero content is edited in CMS Studio
                </p>
            </article>

            <article class="{{ $cardClass }}">
                <div class="flex items-start justify-between gap-4">
                    <h2 class="{{ $cardTitleClass }}">
                        Website Setup
                    </h2>

                    <span class="{{ $badgeBaseClass }} {{ $isSetupComplete ? $readyBadgeClass : $warningBadgeClass }}">
                        {{ $isSetupComplete ? 'Ready' : 'Needs Setup' }}
                    </span>
                </div>

                <p class="mt-5 {{ $metricValueClass }}">
                    {{ $isSetupComplete ? 'Complete' : 'Incomplete' }}
                </p>

                <p class="mt-1 {{ $smallLabelClass }}">
                    Header, footer, menu, and settings
                </p>
            </article>
        </section>

        <section class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <article class="{{ $cardClass }} xl:col-span-2">
                <h2 class="{{ $cardTitleClass }}">
                    Website Setup
                </h2>

                <div class="mt-3">
                    @foreach ($setupRows as $row)
                        <div class="flex items-center justify-between gap-4 border-b border-slate-100 py-4 last:border-b-0 dark:border-gray-700">
                            <div class="min-w-0">
                                <p class="font-sans text-sm font-semibold text-slate-950 dark:text-white">
                                    {{ $row['label'] }}
                                </p>

                                <p class="mt-1 truncate {{ $smallLabelClass }}">
                                    {{ $row['value'] }}
                                </p>
                            </div>

                            <span class="{{ $badgeBaseClass }} shrink-0 {{ $row['class'] }}">
                                {{ $row['badge'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </article>

            <article class="{{ $cardClass }}">
                <h2 class="{{ $cardTitleClass }}">
                    Website Assets
                </h2>

                <div class="mt-3">
                    @foreach ($contentRows as $row)
                        <div class="flex items-center justify-between gap-4 border-b border-slate-100 py-3 last:border-b-0 dark:border-gray-700">
                            <span class="{{ $smallLabelClass }}">
                                {{ $row['label'] }}
                            </span>

                            <span class="font-sans text-base font-semibold text-slate-950 dark:text-white">
                                {{ $row['value'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </article>
        </section>

        <section class="{{ $cardClass }}">
            <h2 class="{{ $cardTitleClass }}">
                Quick Actions
            </h2>

            <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($quickActions as $action)
                    <a
                        href="{{ $action['url'] }}"
                        class="rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-blue-200 hover:bg-blue-50/50 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-blue-700 dark:hover:bg-blue-950/20"
                    >
                        <span class="font-sans text-base font-semibold text-slate-950 dark:text-white">
                            {{ $action['title'] }}
                        </span>

                        <span class="mt-1 block text-sm leading-6 text-slate-500 dark:text-gray-400">
                            {{ $action['description'] }}
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    </div>
</x-admin::layouts>
