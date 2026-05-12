@php
    $headerView = app(\Platform\ThemeDefault\ViewModels\StorefrontHeaderViewModel::class)->build([
        ['label' => 'Home', 'url' => route('shop.home.index')],
        ['label' => 'Products', 'url' => route('shop.search.index')],
        ['label' => 'Categories', 'url' => route('shop.search.index')],
        ['label' => 'Contact', 'url' => route('shop.home.contact_us')],
    ]);

    $brandName = $headerView['brandName'];
    $logoUrl = $headerView['logoUrl'];
    $primaryLinks = $headerView['links'];
    $features = $headerView['features'];
    $announcement = $headerView['announcement'];
@endphp

<header class="gadget-header {{ $features['sticky'] ? '' : 'gadget-header--static' }}">
    @if ($announcement['enabled'] && filled($announcement['text']))
        <div class="gadget-header__announcement">
            @if (filled($announcement['link']))
                <a href="{{ $announcement['link'] }}">{{ $announcement['text'] }}</a>
            @else
                <span>{{ $announcement['text'] }}</span>
            @endif
        </div>
    @endif

    <div class="gadget-header__inner">
        <a href="{{ $headerView['homeUrl'] }}" class="gadget-header__brand" aria-label="{{ $brandName }}">
            @if (filled($logoUrl))
                <img src="{{ $logoUrl }}" alt="" onerror="this.remove()">
            @else
                <span>{{ $brandName }}</span>
            @endif
        </a>

        <nav class="gadget-header__nav" aria-label="Primary navigation">
            @foreach ($primaryLinks as $link)
                <a
                    href="{{ $link['url'] }}"
                    @if ($link['open_in_new_tab']) target="_blank" rel="noopener noreferrer" @endif
                >
                    {{ $link['label'] }}
                </a>
            @endforeach
        </nav>

        @if ($features['show_search'] || $features['show_account'] || $features['show_cart'])
            <div class="gadget-header__tools">
                @if ($features['show_search'])
                    <form action="{{ $headerView['searchUrl'] }}" method="GET" class="gadget-header__search">
                        <input type="search" name="query" value="{{ request('query') }}" aria-label="Search products" placeholder="Search">
                        <button type="submit" aria-label="Search products">
                            <span></span>
                        </button>
                    </form>
                @endif

                @if ($features['show_account'])
                    <a href="{{ $headerView['accountUrl'] }}" class="gadget-header__icon-link" aria-label="Account">
                        <span class="gadget-icon gadget-icon--account"></span>
                    </a>
                @endif

                @if ($features['show_cart'])
                    <a href="{{ $headerView['cartUrl'] }}" class="gadget-header__cart" aria-label="Cart">
                        <span class="gadget-icon gadget-icon--cart"></span>
                    </a>
                @endif
            </div>
        @endif

        <details class="gadget-header__mobile">
            <summary aria-label="Open navigation">
                <span></span>
                <span></span>
                <span></span>
            </summary>

            <div>
                @foreach ($primaryLinks as $link)
                    <a
                        href="{{ $link['url'] }}"
                        @if ($link['open_in_new_tab']) target="_blank" rel="noopener noreferrer" @endif
                    >
                        {{ $link['label'] }}
                    </a>
                @endforeach

                @if ($features['show_account'])
                    <a href="{{ $headerView['accountUrl'] }}">Account</a>
                @endif

                @if ($features['show_cart'])
                    <a href="{{ $headerView['cartUrl'] }}">Cart</a>
                @endif

                @if ($features['show_search'])
                    <form action="{{ $headerView['searchUrl'] }}" method="GET">
                        <input type="search" name="query" value="{{ request('query') }}" aria-label="Search products" placeholder="Search products">
                    </form>
                @endif
            </div>
        </details>
    </div>
</header>
