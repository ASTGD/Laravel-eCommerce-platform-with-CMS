@php($mode = $mode ?? 'standard')
@php($showAction = $showAction ?? true)

@pushOnce('styles')
<style>
    .gadget-product-card {
        background: #ffffff;
        border-radius: 28px;
        padding: 18px;
        border: 1px solid #f1f5f9;
        transition: all 0.5s cubic-bezier(0.23, 1, 0.32, 1);
        position: relative;
        display: flex;
        flex-direction: column;
        height: 100%;
        overflow: hidden;
    }

    .gadget-product-card:hover {
        transform: translateY(-12px);
        box-shadow: 0 30px 60px rgba(0, 0, 0, 0.08);
        border-color: #3b82f6;
    }

    .gadget-product-card__media {
        position: relative;
        aspect-ratio: 1;
        background: #f8fafc;
        border-radius: 20px;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 22px;
    }

    .gadget-product-card__media img {
        width: 80%;
        height: 80%;
        object-fit: contain;
        transition: 0.6s cubic-bezier(0.23, 1, 0.32, 1);
    }

    .gadget-product-card:hover .gadget-product-card__media img {
        transform: scale(1.15) rotate(2deg);
    }

    .gadget-badges {
        position: absolute;
        top: 15px;
        left: 15px;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
        z-index: 20;
        pointer-events: none;
    }

    .gadget-pill {
        display: inline-flex;
        align-items: center;
        padding: 6px 14px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        white-space: nowrap;
        position: relative !important; /* Ensure no absolute positioning */
        top: auto !important;
        left: auto !important;
    }

    .gadget-pill--sale { background: #ef4444; }
    .gadget-pill--hot { background: linear-gradient(45deg, #f59e0b, #ef4444); }
    .gadget-pill--new { background: #3b82f6; }
    .gadget-pill--out { background: #64748b; }

    .gadget-product-card__body {
        flex: 1;
        display: flex;
        flex-direction: column;
        position: relative;
    }

    .gadget-product-card__body h3 {
        font-size: 17px;
        font-weight: 800;
        margin-bottom: 14px;
        line-height: 1.4;
    }

    .gadget-product-card__body h3 a {
        color: #0f172a;
        text-decoration: none;
        transition: 0.3s;
    }

    .gadget-product-card__prices {
        display: flex;
        align-items: baseline;
        gap: 10px;
        margin-top: auto;
        transition: 0.3s;
    }

    .gadget-price--final {
        font-size: 22px;
        font-weight: 950;
        color: #2563eb;
    }

    .gadget-price--regular {
        font-size: 15px;
        color: #94a3b8;
        text-decoration: line-through;
    }

    /* HOVER ACTIONS */
    .gadget-card-actions {
        position: absolute;
        bottom: -60px;
        left: 0;
        right: 0;
        padding: 0;
        transition: 0.4s cubic-bezier(0.23, 1, 0.32, 1);
        opacity: 0;
        display: flex;
        gap: 10px;
    }

    .gadget-product-card:hover .gadget-card-actions {
        bottom: 0;
        opacity: 1;
    }

    .gadget-product-card:hover .gadget-product-card__prices {
        opacity: 0;
        transform: translateY(20px);
    }

    .btn-add-aura {
        flex: 1;
        background: #0f172a;
        color: #ffffff !important;
        padding: 16px;
        border-radius: 14px;
        font-weight: 850;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        font-size: 14px;
        transition: 0.3s;
    }

    .btn-add-aura:hover:not(:disabled) {
        background: #3b82f6;
        box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
    }

    .btn-wish-aura {
        width: 50px;
        height: 50px;
        background: #f1f5f9;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #64748b;
        transition: 0.3s;
        border: none;
        cursor: pointer;
    }

    .btn-wish-aura:hover {
        background: #fee2e2;
        color: #ef4444;
    }

    /* Out of stock card styling */
    .gadget-product-card--out-of-stock {
        opacity: 0.8;
    }
    .gadget-product-card--out-of-stock .gadget-product-card__media {
        filter: grayscale(0.5);
    }
</style>
@endpushOnce

<article class="gadget-product-card {{ ! $product['is_saleable'] ? 'gadget-product-card--out-of-stock' : '' }}">
    <div class="gadget-badges">
        @if (! $product['is_saleable'])
            <span class="gadget-pill gadget-pill--out">Stock Out</span>
        @endif

        @if (! empty($product['badge']))
            <span class="gadget-pill gadget-pill--{{ strtolower($product['badge']) === 'hot' ? 'hot' : (strtolower($product['badge']) === 'sale' ? 'sale' : 'new') }}">
                {{ $product['badge'] }}
            </span>
        @endif
    </div>

    <a href="{{ $product['url'] }}" class="gadget-product-card__media" aria-label="{{ $product['name'] }}">
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
            <div class="gadget-card-actions">
                <button
                    type="button"
                    class="btn-add-aura"
                    data-gadget-add-to-cart
                    data-product-id="{{ $product['id'] }}"
                    data-product-url="{{ $product['url'] }}"
                    data-endpoint="{{ route('shop.api.checkout.cart.store') }}"
                    data-cart-url="{{ route('shop.checkout.cart.index') }}"
                    @disabled(! $product['is_saleable'])
                >
                    @if ($product['is_saleable'])
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                        <span>Buy Now</span>
                    @else
                        <span>Stock Out</span>
                    @endif
                </button>
                <button type="button" class="btn-wish-aura">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                </button>
            </div>
        @endif
    </div>
</article>
