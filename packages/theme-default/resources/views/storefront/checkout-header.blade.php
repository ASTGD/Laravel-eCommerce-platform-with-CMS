@php
    $brandName = core()->getCurrentChannel()->name ?? config('app.name');
    $logoUrl = core()->getCurrentChannel()->logo_url ?? bagisto_asset('images/logo.svg');

    $navigation = $navigation ?? [
        ['label' => 'Home', 'url' => route('shop.home.index')],
        ['label' => 'Collections', 'url' => '#'],
        ['label' => 'Editorial', 'url' => '#'],
        ['label' => 'About', 'url' => '#'],
    ];
@endphp

<header class="border-b border-slate-200 bg-white/90 backdrop-blur">
    <div class="mx-auto flex max-w-[1440px] items-center justify-between gap-6 px-6 py-4 lg:px-10 xl:px-12">
        <a
            href="{{ route('shop.home.index') }}"
            class="flex items-center gap-3"
            aria-label="{{ $brandName }}"
        >
            <img
                src="{{ $logoUrl }}"
                alt="{{ $brandName }}"
                class="h-8 w-auto"
                width="131"
                height="29"
            >
        </a>

        <nav class="hidden flex-1 items-center justify-center gap-10 text-sm font-medium uppercase tracking-[0.24em] text-slate-500 lg:flex">
            @foreach ($navigation as $item)
                <a
                    href="{{ $item['url'] }}"
                    class="transition hover:text-slate-900"
                >
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        <a
            href="{{ route('shop.checkout.cart.index') }}"
            class="flex items-center gap-3 text-xs font-semibold uppercase tracking-[0.28em] text-slate-500 transition hover:text-slate-900"
        >
            <span class="icon-arrow-left text-xl rtl:icon-arrow-right"></span>
            <span class="hidden sm:inline">Return to Cart</span>
            <span class="icon-cart text-2xl"></span>
        </a>
    </div>
</header>
