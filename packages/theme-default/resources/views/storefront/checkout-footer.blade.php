@php
    $brandName = core()->getCurrentChannel()->name ?? config('app.name');
    $logoUrl = core()->getCurrentChannel()->logo_url ?? bagisto_asset('images/logo.svg');

    $columns = [
        [
            'title' => 'Company',
            'links' => [
                ['label' => 'About', 'url' => '#'],
                ['label' => 'Careers', 'url' => '#'],
                ['label' => 'Press', 'url' => '#'],
            ],
        ],
        [
            'title' => 'Support',
            'links' => [
                ['label' => 'Contact', 'url' => '#'],
                ['label' => 'Shipping', 'url' => '#'],
                ['label' => 'Returns', 'url' => '#'],
            ],
        ],
    ];
@endphp

<footer class="border-t border-slate-200 bg-[#f6f3f2]">
    <div class="mx-auto max-w-[1440px] px-6 py-14 lg:px-10 xl:px-12">
        <div class="grid gap-10 md:grid-cols-[1.15fr_0.85fr]">
            <div class="space-y-4">
                <img
                    src="{{ $logoUrl }}"
                    alt="{{ $brandName }}"
                    class="h-8 w-auto"
                    width="131"
                    height="29"
                >

                <p class="max-w-xl text-sm leading-6 text-slate-500">
                    A premium, reusable storefront checkout experience built on a structured commerce core.
                </p>
            </div>

            <div class="grid gap-8 sm:grid-cols-2">
                @foreach ($columns as $column)
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.22em] text-slate-900">
                            {{ $column['title'] }}
                        </p>

                        <div class="mt-4 space-y-3 text-sm text-slate-500">
                            @foreach ($column['links'] as $link)
                                <a
                                    href="{{ $link['url'] }}"
                                    class="block transition hover:text-slate-900"
                                >
                                    {{ $link['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</footer>
