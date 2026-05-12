@php
    $headerView = app(\Platform\ThemeDefault\ViewModels\StorefrontHeaderViewModel::class)->build([
        ['label' => 'Catalog', 'url' => route('shop.search.index')],
        ['label' => 'Contact', 'url' => route('shop.home.contact_us')],
    ]);

    $announcement = $headerView['announcement'];
    $features = $headerView['features'];
@endphp
<header class="{{ $features['sticky'] ? 'sticky top-0 z-40' : '' }} border-b border-slate-200 bg-white/95 backdrop-blur">
    @if ($announcement['enabled'] && filled($announcement['text']))
        <div class="bg-slate-950 px-6 py-2 text-center text-xs font-medium text-white">
            @if (filled($announcement['link']))
                <a href="{{ $announcement['link'] }}" class="hover:text-blue-100">{{ $announcement['text'] }}</a>
            @else
                <span>{{ $announcement['text'] }}</span>
            @endif
        </div>
    @endif

    <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
        <div class="flex min-w-0 items-center gap-3">
            @if (filled($headerView['logoUrl']))
                <a href="{{ $headerView['homeUrl'] }}" aria-label="{{ $headerView['brandName'] }}">
                    <img src="{{ $headerView['logoUrl'] }}" alt="" class="max-h-10 max-w-[140px] object-contain md:max-h-14 md:max-w-[180px]" onerror="this.remove()">
                </a>
            @else
                <a href="{{ $headerView['homeUrl'] }}" class="text-2xl font-semibold text-slate-900">
                    {{ $headerView['brandName'] }}
                </a>
            @endif
        </div>

        <nav class="hidden gap-6 text-sm text-slate-600 md:flex">
            @foreach ($headerView['links'] as $link)
                <a
                    href="{{ $link['url'] }}"
                    class="hover:text-slate-900"
                    @if ($link['open_in_new_tab']) target="_blank" rel="noopener noreferrer" @endif
                >
                    {{ $link['label'] }}
                </a>
            @endforeach
        </nav>

        @if ($features['show_search'] || $features['show_account'] || $features['show_cart'])
            <div class="hidden items-center gap-3 text-sm font-medium text-slate-600 md:flex">
                @if ($features['show_search'])
                    <a href="{{ $headerView['searchUrl'] }}" class="hover:text-slate-900">Search</a>
                @endif

                @if ($features['show_account'])
                    <a href="{{ $headerView['accountUrl'] }}" class="hover:text-slate-900">Account</a>
                @endif

                @if ($features['show_cart'])
                    <a href="{{ $headerView['cartUrl'] }}" class="hover:text-slate-900">Cart</a>
                @endif
            </div>
        @endif
    </div>
</header>
