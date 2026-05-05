@props([
    'title' => null,
])

@php
    $breadcrumbs = collect();

    $findActiveTrail = function ($items, $trail = []) use (&$findActiveTrail) {
        foreach ($items as $item) {
            $nextTrail = array_merge($trail, [$item]);

            if ($item->isActive()) {
                if ($item->haveChildren()) {
                    $childTrail = $findActiveTrail($item->getChildren(), $nextTrail);

                    return $childTrail ?: $nextTrail;
                }

                return $nextTrail;
            }

            if ($item->haveChildren()) {
                $childTrail = $findActiveTrail($item->getChildren(), $nextTrail);

                if ($childTrail) {
                    return $childTrail;
                }
            }
        }

        return null;
    };

    $activeTrail = $findActiveTrail(menu()->getItems('admin'));

    if ($activeTrail) {
        $breadcrumbs = collect($activeTrail)
            ->map(fn ($item) => [
                'label' => $item->getName(),
                'url'   => $item->getUrl(),
            ])
            ->values();
    }

    if ($breadcrumbs->isEmpty() && ! empty($title)) {
        $breadcrumbs = collect([
            [
                'label' => $title,
                'url'   => null,
            ],
        ]);
    }
@endphp

@if ($breadcrumbs->isNotEmpty())
    <nav
        class="mb-4 flex min-h-5 items-center gap-2 overflow-x-auto whitespace-nowrap text-sm font-normal text-slate-500 dark:text-slate-400"
        aria-label="Breadcrumb"
    >
        <a
            href="{{ route('admin.dashboard.index') }}"
            class="inline-flex items-center justify-center font-normal text-slate-500 transition hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300"
            aria-label="Dashboard"
        >
            <svg
                class="h-4 w-4"
                viewBox="0 0 24 24"
                fill="none"
                aria-hidden="true"
            >
                <path
                    d="M3 10.75L12 3l9 7.75"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
                <path
                    d="M5.5 10.25V20h13v-9.75"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
                <path
                    d="M9.5 20v-5.5h5V20"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
            </svg>
        </a>

        @foreach ($breadcrumbs as $breadcrumb)
            <svg
                class="h-3.5 w-3.5 flex-none text-slate-400 rtl:rotate-180 dark:text-gray-500"
                viewBox="0 0 20 20"
                fill="none"
                aria-hidden="true"
            >
                <path
                    d="M7.5 4.5L12.5 10L7.5 15.5"
                    stroke="currentColor"
                    stroke-width="1.7"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
            </svg>

            @if (! $loop->last && ! empty($breadcrumb['url']))
                <a
                    href="{{ $breadcrumb['url'] }}"
                    class="font-normal text-slate-500 transition hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300"
                >
                    {{ $breadcrumb['label'] }}
                </a>
            @else
                <span class="font-normal text-slate-500 dark:text-slate-400">
                    {{ $breadcrumb['label'] }}
                </span>
            @endif
        @endforeach
    </nav>
@endif
