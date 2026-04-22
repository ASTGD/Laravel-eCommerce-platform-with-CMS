@props([
    'paginator',
    'searchAction' => request()->url(),
    'searchPlaceholder' => 'Search',
    'searchValue' => request('search'),
    'searchName' => 'search',
    'pageName' => 'page',
    'perPageName' => 'per_page',
    'perPage' => request('per_page', $paginator?->perPage() ?? 10),
    'perPageOptions' => [10, 25, 50, 100],
    'showFilterButton' => true,
    'filterButtonLabel' => 'Filter',
    'filterDrawerTitle' => 'Filters',
    'filterDrawerWidth' => '350px',
    'preserveQuery' => [],
])

@php
    $searchFormId = 'basic-list-toolbar-'.\Illuminate\Support\Str::uuid();
    $hasFilters = isset($filters) && trim($filters->toHtml()) !== '';
@endphp

<div class="mt-7 flex items-center justify-between gap-4 max-md:flex-wrap">
    <form
        id="{{ $searchFormId }}"
        method="GET"
        action="{{ $searchAction }}"
        class="flex flex-wrap items-center gap-4 max-md:w-full"
    >
        @foreach ($preserveQuery as $key => $value)
            @if (! in_array($key, [$searchName, $pageName, $perPageName], true))
                @if (is_array($value))
                    @foreach ($value as $nestedValue)
                        <input type="hidden" name="{{ $key }}[]" value="{{ $nestedValue }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endif
        @endforeach

        <input type="hidden" name="{{ $pageName }}" value="1">

        <div class="flex max-w-[445px] items-center max-sm:w-full max-sm:max-w-full">
            <div class="relative w-full">
                <input
                    type="text"
                    name="{{ $searchName }}"
                    value="{{ $searchValue }}"
                    class="block w-full rounded-lg border bg-white py-1.5 leading-6 text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400 ltr:pl-3 ltr:pr-10 rtl:pl-10 rtl:pr-3"
                    placeholder="{{ $searchPlaceholder }}"
                    autocomplete="off"
                >

                <div class="icon-search pointer-events-none absolute top-2 flex items-center text-2xl ltr:right-2.5 rtl:left-2.5"></div>
            </div>
        </div>

        <div class="ltr:pl-2.5 rtl:pr-2.5">
            <p class="text-sm font-light text-gray-800 dark:text-white">
                {{ $paginator->total() }} Results
            </p>
        </div>
    </form>

    <div class="flex items-center gap-x-4 max-md:w-full max-md:justify-between">
        @if ($showFilterButton)
            <x-admin::drawer width="{{ $filterDrawerWidth }}">
                <x-slot:toggle>
                    <div class="relative inline-flex w-full max-w-max cursor-pointer select-none appearance-none items-center justify-between gap-x-1 rounded-md border bg-white px-1 py-1.5 text-center text-gray-600 transition-all marker:shadow hover:border-gray-400 focus:outline-none focus:ring-2 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 ltr:pl-3 ltr:pr-5 rtl:pl-5 rtl:pr-3">
                        <span class="icon-filter text-2xl"></span>

                        <span>
                            {{ $filterButtonLabel }}
                        </span>
                    </div>
                </x-slot>

                <x-slot:header>
                    <div class="flex items-center justify-between gap-2.5">
                        <p class="text-xl font-semibold text-gray-800 dark:text-white">
                            {{ $filterDrawerTitle }}
                        </p>
                    </div>
                </x-slot>

                <x-slot:content class="!p-0">
                    @if ($hasFilters)
                        {{ $filters }}
                    @else
                        <div class="p-4 text-sm text-gray-600 dark:text-gray-300">
                            No additional filters are available for this page.
                        </div>
                    @endif
                </x-slot>
            </x-admin::drawer>
        @endif

        <div class="flex items-center gap-x-2">
            <x-admin::dropdown>
                <x-slot:toggle>
                    <button
                        type="button"
                        class="inline-flex w-full max-w-max cursor-pointer appearance-none items-center justify-between gap-x-2 rounded-md border bg-white px-2.5 py-1.5 text-center leading-6 text-gray-600 transition-all marker:shadow hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400"
                    >
                        <span>
                            {{ $perPage }}
                        </span>

                        <span class="icon-sort-down text-2xl"></span>
                    </button>
                </x-slot>

                <x-slot:menu>
                    @foreach ($perPageOptions as $option)
                        <button
                            type="submit"
                            form="{{ $searchFormId }}"
                            name="{{ $perPageName }}"
                            value="{{ $option }}"
                            class="block w-full px-3 py-2 text-left text-sm text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800"
                            onclick="document.getElementById('{{ $searchFormId }}').querySelector('[name={{ $pageName }}]').value = 1;"
                        >
                            {{ $option }}
                        </button>
                    @endforeach
                </x-slot>
            </x-admin::dropdown>

            <p class="whitespace-nowrap text-gray-600 dark:text-gray-300 max-sm:hidden">
                Per Page
            </p>

            <input
                type="text"
                form="{{ $searchFormId }}"
                name="{{ $pageName }}"
                class="inline-flex min-h-[38px] max-w-10 appearance-none items-center justify-center gap-x-1 rounded-md border bg-white px-3 py-1.5 text-center leading-6 text-gray-600 transition-all marker:shadow hover:border-gray-400 focus:border-gray-400 focus:outline-none dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400 max-sm:hidden"
                value="{{ $paginator->currentPage() }}"
                onchange="this.form.submit()"
            >

            <div class="whitespace-nowrap text-gray-600 dark:text-gray-300">
                <span>
                    of
                </span>

                <span>
                    {{ $paginator->lastPage() }}
                </span>
            </div>

            <div class="flex items-center gap-1">
                <a
                    @if ($paginator->previousPageUrl())
                        href="{{ $paginator->previousPageUrl() }}"
                    @else
                        href="javascript:void(0);"
                    @endif
                    class="inline-flex w-full max-w-max cursor-pointer appearance-none items-center justify-between gap-x-1 rounded-md border border-transparent p-1.5 text-center text-gray-600 transition-all marker:shadow hover:bg-gray-200 active:border-gray-300 dark:text-gray-300 dark:hover:bg-gray-800"
                    @if (! $paginator->previousPageUrl()) aria-disabled="true" @endif
                >
                    <span class="icon-sort-left rtl:icon-sort-right text-2xl"></span>
                </a>

                <a
                    @if ($paginator->nextPageUrl())
                        href="{{ $paginator->nextPageUrl() }}"
                    @else
                        href="javascript:void(0);"
                    @endif
                    class="inline-flex w-full max-w-max cursor-pointer appearance-none items-center justify-between gap-x-1 rounded-md border border-transparent p-1.5 text-center text-gray-600 transition-all marker:shadow hover:bg-gray-200 active:border-gray-300 dark:text-gray-300 dark:hover:bg-gray-800"
                    @if (! $paginator->nextPageUrl()) aria-disabled="true" @endif
                >
                    <span class="icon-sort-right rtl:icon-sort-left text-2xl"></span>
                </a>
            </div>
        </div>
    </div>
</div>
