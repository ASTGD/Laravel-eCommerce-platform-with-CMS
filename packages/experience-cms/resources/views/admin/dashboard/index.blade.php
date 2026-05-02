<x-admin::layouts>
    <x-slot:title>CMS Dashboard</x-slot:title>

    <div class="space-y-6 pb-8">
        <section class="relative overflow-hidden rounded-[2rem] border border-slate-200 bg-slate-950 px-6 py-6 text-white shadow-2xl shadow-slate-950/20 lg:px-8 lg:py-8 dark:border-slate-800">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(251,146,60,0.22),_transparent_42%),radial-gradient(circle_at_bottom_left,_rgba(59,130,246,0.16),_transparent_35%)]"></div>

            <div class="relative grid gap-6 lg:grid-cols-[minmax(0,1.5fr)_minmax(320px,0.85fr)] lg:items-end">
                <div class="space-y-4">
                    <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-4 py-2 text-xs font-semibold uppercase tracking-[0.28em] text-slate-200">
                        Platform CMS
                    </div>

                    <div class="space-y-3">
                        <h1 class="text-3xl font-semibold tracking-tight text-white md:text-4xl">
                            CMS Dashboard
                        </h1>

                        <p class="max-w-2xl text-sm leading-6 text-slate-300 md:text-base">
                            Manage pages, templates, presets, and structured content from one overview screen before drilling into the detailed workflows.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <a
                            href="{{ route('admin.cms.pages.create') }}"
                            class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-slate-100"
                        >
                            New page
                        </a>

                        <a
                            href="{{ route('admin.cms.pages.index') }}"
                            class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/10"
                        >
                            View pages
                        </a>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <article class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-300">
                            Most used template
                        </p>
                        <p class="mt-2 text-lg font-semibold text-white">
                            {{ $mostUsedTemplate?->name ?? 'No active template yet' }}
                        </p>
                        <p class="mt-1 text-sm text-slate-300">
                            {{ $mostUsedTemplate?->pages_count ?? 0 }} pages currently use this layout.
                        </p>
                    </article>

                    <article class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-300">
                            Latest publish
                        </p>
                        <p class="mt-2 text-lg font-semibold text-white">
                            {{ $latestPublishedPage?->title ?? 'Nothing published yet' }}
                        </p>
                        <p class="mt-1 text-sm text-slate-300">
                            {{ $latestPublishedPage?->published_at?->diffForHumans() ?? 'Publish a page to populate this card.' }}
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
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

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1.65fr)_minmax(320px,0.85fr)]">
            <article class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm shadow-slate-200/60 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                <div class="flex flex-col gap-4 border-b border-slate-200 px-6 py-6 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">
                            Recent pages
                        </p>
                        <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950 dark:text-white">
                            Working set
                        </h2>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500 dark:text-slate-400">
                            The latest pages in this workspace, grouped by template and status for quick review.
                        </p>
                    </div>

                    <a
                        href="{{ route('admin.cms.pages.create') }}"
                        class="inline-flex items-center rounded-full bg-slate-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-100"
                    >
                        Create page
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full border-separate border-spacing-0 text-left">
                        <thead class="bg-slate-50/80 text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:bg-slate-950 dark:text-slate-400">
                            <tr>
                                <th class="px-6 py-4">Page</th>
                                <th class="px-6 py-4">Template</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4">Updated</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                            @forelse ($pages as $page)
                                <tr class="bg-white transition hover:bg-slate-50/80 dark:bg-slate-900 dark:hover:bg-slate-800/60">
                                    <td class="px-6 py-5">
                                        <div class="space-y-1">
                                            <p class="text-base font-semibold text-slate-950 dark:text-white">
                                                {{ $page->title }}
                                            </p>

                                            <p class="font-mono text-xs uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                                                {{ $page->slug }}
                                            </p>
                                        </div>
                                    </td>

                                    <td class="px-6 py-5 text-sm text-slate-600 dark:text-slate-300">
                                        {{ $page->template?->name ?? 'No template' }}
                                    </td>

                                    <td class="px-6 py-5">
                                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] {{ $page->isPublished() ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }}">
                                            {{ $page->isPublished() ? 'Published' : 'Draft' }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-5 text-sm text-slate-600 dark:text-slate-300">
                                        {{ $page->updated_at?->diffForHumans() ?? '—' }}
                                    </td>

                                    <td class="px-6 py-5 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a
                                                href="{{ route('admin.cms.pages.preview', $page) }}"
                                                target="_blank"
                                                class="inline-flex items-center rounded-full border border-slate-200 px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
                                            >
                                                Preview
                                            </a>

                                            <a
                                                href="{{ route('admin.cms.pages.edit', $page) }}"
                                                class="inline-flex items-center rounded-full border border-slate-200 px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
                                            >
                                                Edit
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-16 text-center">
                                        <div class="mx-auto max-w-md space-y-3">
                                            <p class="text-lg font-semibold text-slate-950 dark:text-white">
                                                No pages created yet.
                                            </p>

                                            <p class="text-sm leading-6 text-slate-500 dark:text-slate-400">
                                                Create the first structured page to populate this dashboard and start the CMS workflow.
                                            </p>

                                            <a
                                                href="{{ route('admin.cms.pages.create') }}"
                                                class="inline-flex items-center rounded-full bg-slate-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 dark:bg-white dark:text-slate-950"
                                            >
                                                Create page
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>

            <div class="space-y-6">
                <article class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm shadow-slate-200/60 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                    <div class="border-b border-slate-200 px-6 py-6 dark:border-slate-800">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-slate-400">
                            Quick actions
                        </p>
                        <h3 class="mt-2 text-xl font-semibold tracking-tight text-slate-950 dark:text-white">
                            Jump to the working areas
                        </h3>
                    </div>

                    <div class="grid gap-3 px-6 py-6">
                        @foreach ($quickLinks as $link)
                            <a
                                href="{{ $link['route'] }}"
                                class="group rounded-2xl border border-slate-200 px-4 py-4 transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800/60"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-950 transition group-hover:text-slate-900 dark:text-white">
                                            {{ $link['label'] }}
                                        </p>
                                        <p class="mt-1 text-sm leading-6 text-slate-500 dark:text-slate-400">
                                            {{ $link['description'] }}
                                        </p>
                                    </div>

                                    <span class="text-lg text-slate-400 transition group-hover:text-slate-600 dark:text-slate-500">
                                        →
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </article>

                <article class="rounded-[2rem] border border-slate-200 bg-slate-950 text-white shadow-sm shadow-slate-200/60 dark:border-slate-800 dark:shadow-none">
                    <div class="border-b border-white/10 px-6 py-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-300">
                            Workspace inventory
                        </p>
                        <h3 class="mt-2 text-xl font-semibold tracking-tight">
                            Structured CMS assets
                        </h3>
                    </div>

                    <div class="grid gap-3 px-6 py-6">
                        @foreach ($inventory as $item)
                            <div class="flex items-center justify-between gap-4 rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                                <p class="text-sm font-medium text-white">
                                    {{ $item['label'] }}
                                </p>

                                <p class="text-sm font-semibold text-slate-200">
                                    {{ $item['value'] }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </article>
            </div>
        </section>
    </div>
</x-admin::layouts>
