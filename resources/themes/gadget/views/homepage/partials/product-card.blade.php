@php($mode = $mode ?? 'standard')
@php($showAction = $showAction ?? false)

<article class="gadget-product-card {{ $mode === 'latest' ? 'gadget-product-card--tall' : '' }}">
    <a href="{{ $product['url'] }}" class="gadget-product-card__media" aria-label="{{ $product['name'] }}">
        @if (! empty($product['badge']))
            <span class="gadget-pill">{{ $product['badge'] }}</span>
        @endif

        <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}" loading="lazy">
    </a>

    <div class="gadget-product-card__body">
        <h3>
            <a href="{{ $product['url'] }}">{{ $product['short_name'] }}</a>
        </h3>

        <div class="gadget-product-card__prices">
            @if ($product['has_discount'])
                <span class="gadget-price gadget-price--regular">{{ $product['regular_price'] }}</span>
            @endif

            <span class="gadget-price gadget-price--final">{{ $product['final_price'] }}</span>
        </div>

        @if ($showAction)
            <button
                type="button"
                class="gadget-add-button"
                data-gadget-add-to-cart
                data-product-id="{{ $product['id'] }}"
                data-product-url="{{ $product['url'] }}"
                data-endpoint="{{ route('shop.api.checkout.cart.store') }}"
                data-cart-url="{{ route('shop.checkout.cart.index') }}"
                @disabled(! $product['is_saleable'])
            >
                <span></span>
                Add to Cart
            </button>
        @endif
    </div>
</article>
