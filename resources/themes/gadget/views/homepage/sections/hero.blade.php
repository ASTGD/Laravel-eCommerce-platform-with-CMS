@php($heroProducts = collect($products)->take(3)->values())

<section class="gadget-hero">
    <div class="gadget-container gadget-hero__grid">
        <div class="gadget-hero__copy">
            <h1>Ensuring Happiness At Every Step</h1>
            <p>
                Explore smart gadgets, daily tech essentials, and fast home delivery with a storefront built for focused shopping.
            </p>

            <div class="gadget-button-row">
                <a href="{{ route('shop.search.index') }}" class="gadget-button gadget-button--primary">
                    <span></span>
                    Shop Now
                </a>

                <a href="{{ route('shop.search.index') }}" class="gadget-button gadget-button--outline">
                    Our All Products
                </a>
            </div>
        </div>

        <div class="gadget-hero__art" aria-hidden="true">
            @forelse ($heroProducts as $index => $product)
                <div class="gadget-hero__orb gadget-hero__orb--{{ $index + 1 }}">
                    <img src="{{ $product['image'] }}" alt="">
                </div>
            @empty
                <div class="gadget-hero__orb gadget-hero__orb--1">
                    <img src="{{ bagisto_asset('images/medium-product-placeholder.webp', 'shop') }}" alt="">
                </div>
            @endforelse
        </div>
    </div>
</section>
