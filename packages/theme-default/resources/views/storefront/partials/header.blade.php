<header class="storefront-header">
    <div class="announcement">{{ $header?->settings_json['announcement'] ?? config('app.name') }}</div>
    <div class="header-bar">
        <a href="{{ route('storefront.home') }}" class="brand-mark">{{ config('platform.brand_name') }}</a>

        <nav class="primary-nav">
            @foreach ($primaryMenu?->items ?? [] as $item)
                <a href="{{ $item->target }}">{{ $item->title }}</a>
            @endforeach
        </nav>

        <div class="header-tools">
            @if ($header?->settings_json['show_search'] ?? false)
                <span>Search</span>
            @endif
            @if ($header?->settings_json['show_account'] ?? false)
                <span>Account</span>
            @endif
            @if ($header?->settings_json['show_cart'] ?? false)
                <span>Cart</span>
            @endif
        </div>
    </div>
</header>
