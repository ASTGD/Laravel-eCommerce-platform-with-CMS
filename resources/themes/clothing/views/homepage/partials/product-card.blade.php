@php($mode = $mode ?? 'standard')
@php($showAction = $showAction ?? false)

@pushOnce('styles')
<style>
    .gadget-product-card {
        position: relative;
        display: grid;
        grid-template-rows: auto 1fr;
        height: 100%;
        background: transparent;
        border: 0;
        padding: 0;
        overflow: visible;
    }

    .gadget-product-card__media {
        position: relative;
        aspect-ratio: 4 / 5;
        border-radius: 34px;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        background:
            radial-gradient(circle at 24% 18%, rgba(255, 209, 102, .55), transparent 32%),
            radial-gradient(circle at 82% 12%, rgba(124, 92, 255, .32), transparent 30%),
            linear-gradient(135deg, #fff1f3, #fff8e8);
        text-decoration: none;
        box-shadow: 0 18px 45px rgba(23, 17, 20, .08);
        isolation: isolate;
    }

    .gadget-product-card__media::after {
        content: '';
        position: absolute;
        inset: 14px;
        border: 1px solid rgba(255,255,255,.72);
        border-radius: 24px;
        pointer-events: none;
        z-index: 2;
    }

    .gadget-product-card__media img {
        width: 88%;
        height: 88%;
        object-fit: contain;
        transition: transform .45s ease;
        filter: drop-shadow(0 22px 28px rgba(23,17,20,.12));
        z-index: 1;
    }

    .gadget-product-card:hover .gadget-product-card__media img { transform: scale(1.055) rotate(-1.5deg); }

    .gadget-pill {
        position: absolute;
        top: 16px;
        left: 16px;
        z-index: 3;
        background: #171114;
        color: #fff;
        padding: 8px 13px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 900;
        letter-spacing: .09em;
        text-transform: uppercase;
    }

    .gadget-product-card__body {
        padding: 18px 4px 0;
        display: grid;
        gap: 10px;
    }

    .gadget-product-card__body h3 {
        font-size: 18px;
        line-height: 1.22;
        font-weight: 850;
        letter-spacing: -0.03em;
        margin: 0;
    }

    .gadget-product-card__body h3 a { color: #171114; text-decoration: none; }
    .gadget-product-card__body h3 a:hover { color: #ff4f70; }

    .gadget-product-card__prices { display: flex; align-items: center; gap: 9px; flex-wrap: wrap; }
    .gadget-price--final { color: #171114; font-size: 21px; font-weight: 950; }
    .gadget-price--regular { color: #9d9296; font-size: 14px; text-decoration: line-through; font-weight: 700; }

    .gadget-add-button {
        margin-top: 6px;
        border: 0;
        border-radius: 999px;
        padding: 13px 18px;
        background: #171114;
        color: #fff;
        font-weight: 900;
        cursor: pointer;
        display: inline-flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
        transition: transform .25s ease, background .25s ease;
    }

    .gadget-add-button:hover:not(:disabled) { transform: translateY(-2px); background: #ff4f70; }
    .gadget-add-button:disabled { opacity: .45; cursor: not-allowed; }
</style>
@endPushOnce

<article class="gadget-product-card {{ $mode === 'latest' ? 'gadget-product-card--tall' : '' }}">
    <a href="{{ $product['url'] }}" class="gadget-product-card__media" aria-label="{{ $product['name'] }}">
        @if (! empty($product['badge']))
            <span class="gadget-pill">{{ $product['badge'] }}</span>
        @endif

        <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}" loading="lazy">
    </a>

    <div class="gadget-product-card__body">
        <h3><a href="{{ $product['url'] }}">{{ $product['short_name'] }}</a></h3>

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
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                Add to Bag
            </button>
        @endif
    </div>
</article>
