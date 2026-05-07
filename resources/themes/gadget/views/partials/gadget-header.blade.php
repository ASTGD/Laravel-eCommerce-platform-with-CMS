@php
    $channel = core()->getCurrentChannel();
    $brandName = $channel?->name ?? config('app.name');
    $logoUrl = $channel?->logo_url ?: asset('images/astgd-ecommerce-logo.webp');
    $accountUrl = auth()->guard('customer')->check()
        ? route('shop.customers.account.index')
        : route('shop.customer.session.index');
    $primaryLinks = [
        ['label' => 'Home', 'url' => route('shop.home.index')],
        ['label' => 'Products', 'url' => route('shop.search.index')],
        ['label' => 'Categories', 'url' => route('shop.search.index')],
        ['label' => 'Contact', 'url' => route('shop.home.contact_us')],
    ];
@endphp

<header class="gadget-header">
    <div class="gadget-header__inner">
        <a href="{{ route('shop.home.index') }}" class="gadget-header__brand" aria-label="{{ $brandName }}">
            <img src="{{ $logoUrl }}" alt="" onerror="this.remove()">
            <span>{{ $brandName }}</span>
        </a>

        <nav class="gadget-header__nav" aria-label="Primary navigation">
            @foreach ($primaryLinks as $link)
                <a href="{{ $link['url'] }}">{{ $link['label'] }}</a>
            @endforeach
        </nav>

        <div class="gadget-header__tools">
            <form action="{{ route('shop.search.index') }}" method="GET" class="gadget-header__search">
                <input type="search" name="query" value="{{ request('query') }}" aria-label="Search products" placeholder="Search">
                <button type="submit" aria-label="Search products">
                    <span></span>
                </button>
            </form>

            <a href="{{ $accountUrl }}" class="gadget-header__icon-link" aria-label="Account">
                <span class="gadget-icon gadget-icon--account"></span>
            </a>

            <a href="{{ route('shop.checkout.cart.index') }}" class="gadget-header__cart" aria-label="Cart">
                <span class="gadget-icon gadget-icon--cart"></span>
            </a>
        </div>

        <details class="gadget-header__mobile">
            <summary aria-label="Open navigation">
                <span></span>
                <span></span>
                <span></span>
            </summary>

            <div>
                @foreach ($primaryLinks as $link)
                    <a href="{{ $link['url'] }}">{{ $link['label'] }}</a>
                @endforeach

                <a href="{{ $accountUrl }}">Account</a>
                <a href="{{ route('shop.checkout.cart.index') }}">Cart</a>

                <form action="{{ route('shop.search.index') }}" method="GET">
                    <input type="search" name="query" value="{{ request('query') }}" aria-label="Search products" placeholder="Search products">
                </form>
            </div>
        </details>
    </div>
</header>
