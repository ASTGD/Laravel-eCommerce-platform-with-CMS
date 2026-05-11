@php
    $channel = core()->getCurrentChannel();
    $brandName = $channel?->name ?? config('app.name');
    $logoUrl = $channel?->logo_url ?: asset('images/astgd-ecommerce-logo.webp');
    $accountUrl = auth()->guard('customer')->check()
        ? route('shop.customers.account.index')
        : route('shop.customer.session.index');
    $primaryLinks = [
        ['label' => 'New In', 'url' => route('shop.search.index')],
        ['label' => 'Dresses', 'url' => route('shop.search.index', ['query' => 'dress'])],
        ['label' => 'Collections', 'url' => route('shop.search.index')],
        ['label' => 'Contact', 'url' => route('shop.home.contact_us')],
    ];
@endphp

@pushOnce('styles')
<style>
    .gadget-header {
        position: sticky;
        top: 0;
        z-index: 1000;
        background: rgba(255, 253, 250, .82);
        backdrop-filter: blur(18px);
        border-bottom: 1px solid rgba(23, 17, 20, .08);
    }

    .gadget-header__inner {
        width: min(1220px, calc(100% - 40px));
        min-height: 82px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: auto 1fr auto;
        align-items: center;
        gap: 28px;
    }

    .gadget-header__brand {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        color: #171114;
        text-decoration: none !important;
        font-weight: 900;
        letter-spacing: -0.04em;
        font-size: 23px;
    }

    .gadget-header__brand img { width: 44px; height: 44px; object-fit: contain; border-radius: 14px; }
    .gadget-header__brand span::after { content: ' atelier'; color: #ff4f70; font-weight: 700; margin-left: 4px; }

    .gadget-header__nav {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .gadget-header__nav a {
        color: #473f42;
        text-decoration: none;
        font-size: 15px;
        font-weight: 800;
        padding: 10px 15px;
        border-radius: 999px;
        transition: .25s ease;
    }

    .gadget-header__nav a:hover { color: #171114; background: #fff1f3; }

    .gadget-header__tools { display: flex; align-items: center; gap: 12px; }

    .gadget-header__search {
        display: flex;
        align-items: center;
        background: #fff;
        border: 1px solid rgba(23,17,20,.12);
        border-radius: 999px;
        padding: 6px 8px 6px 16px;
        min-width: 230px;
        box-shadow: 0 10px 30px rgba(23,17,20,.05);
    }

    .gadget-header__search input {
        width: 100%;
        border: 0;
        outline: 0;
        background: transparent;
        color: #171114;
        font: inherit;
    }

    .gadget-header__search button,
    .gadget-header__icon-link,
    .gadget-header__cart {
        width: 42px;
        height: 42px;
        border: 0;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #171114;
        color: #fff;
        text-decoration: none;
        cursor: pointer;
        transition: transform .25s ease, background .25s ease;
    }

    .gadget-header__search button:hover,
    .gadget-header__icon-link:hover,
    .gadget-header__cart:hover { transform: translateY(-2px); background: #ff4f70; }

    .gadget-header__search button span,
    .gadget-icon { width: 18px; height: 18px; display: block; position: relative; }
    .gadget-header__search button span::before { content: '⌕'; font-size: 23px; line-height: 16px; }
    .gadget-icon--account::before { content: '♡'; font-size: 22px; line-height: 18px; }
    .gadget-icon--cart::before { content: '◎'; font-size: 22px; line-height: 18px; }

    .gadget-header__mobile { display: none; }

    @media (max-width: 980px) {
        .gadget-header__inner { grid-template-columns: 1fr auto; min-height: 72px; width: min(100% - 28px, 1220px); }
        .gadget-header__nav, .gadget-header__tools { display: none; }
        .gadget-header__mobile { display: block; position: relative; }
        .gadget-header__mobile summary { list-style: none; width: 46px; height: 46px; border-radius: 50%; background: #171114; display: grid; place-content: center; gap: 5px; cursor: pointer; }
        .gadget-header__mobile summary::-webkit-details-marker { display: none; }
        .gadget-header__mobile summary span { width: 20px; height: 2px; background: #fff; display: block; }
        .gadget-header__mobile > div { position: absolute; right: 0; top: 58px; width: min(300px, calc(100vw - 28px)); background: #fff; border: 1px solid rgba(23,17,20,.12); border-radius: 26px; padding: 16px; box-shadow: 0 24px 70px rgba(23,17,20,.14); }
        .gadget-header__mobile a { display: block; color: #171114; text-decoration: none; font-weight: 800; padding: 13px 12px; border-radius: 16px; }
        .gadget-header__mobile a:hover { background: #fff1f3; }
        .gadget-header__mobile input { width: 100%; border: 1px solid rgba(23,17,20,.12); border-radius: 999px; padding: 13px 14px; margin-top: 10px; font: inherit; }
    }
</style>
@endPushOnce

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
                <input type="search" name="query" value="{{ request('query') }}" aria-label="Search products" placeholder="Search outfits">
                <button type="submit" aria-label="Search products"><span></span></button>
            </form>

            <a href="{{ $accountUrl }}" class="gadget-header__icon-link" aria-label="Account"><span class="gadget-icon gadget-icon--account"></span></a>
            <a href="{{ route('shop.checkout.cart.index') }}" class="gadget-header__cart" aria-label="Cart"><span class="gadget-icon gadget-icon--cart"></span></a>
        </div>

        <details class="gadget-header__mobile">
            <summary aria-label="Open navigation"><span></span><span></span><span></span></summary>
            <div>
                @foreach ($primaryLinks as $link)
                    <a href="{{ $link['url'] }}">{{ $link['label'] }}</a>
                @endforeach
                <a href="{{ $accountUrl }}">Account</a>
                <a href="{{ route('shop.checkout.cart.index') }}">Cart</a>
                <form action="{{ route('shop.search.index') }}" method="GET">
                    <input type="search" name="query" value="{{ request('query') }}" aria-label="Search products" placeholder="Search outfits">
                </form>
            </div>
        </details>
    </div>
</header>
