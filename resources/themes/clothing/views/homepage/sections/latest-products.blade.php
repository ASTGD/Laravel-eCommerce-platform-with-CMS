@pushOnce('styles')
<style>
    .gadget-latest { background: #fffdfa; }
    .gadget-product-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: clamp(18px, 3vw, 34px); }
    @media (max-width: 1200px) { .gadget-product-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 860px) { .gadget-product-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 520px) { .gadget-product-grid { grid-template-columns: 1fr; } }
</style>
@endPushOnce

<section class="gadget-section gadget-latest" aria-labelledby="gadget-latest-title">
    <div class="gadget-container">
        <div class="gadget-section-heading">
            <div>
                <h2 id="gadget-latest-title">Just landed</h2>
                <p>Fresh pieces for quick outfit updates, from everyday layers to color-led statement looks.</p>
            </div>

            <a href="{{ route('shop.search.index') }}" class="gadget-text-link">
                View All
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
            </a>
        </div>

        @php
            $displayProducts = count($products) > 0 ? $products : array_fill(0, 4, [
                'id' => 0,
                'name' => 'Everyday Statement Piece',
                'short_name' => 'Everyday Statement Piece',
                'url' => '#',
                'image' => bagisto_asset('images/medium-product-placeholder.webp', 'shop'),
                'regular_price' => '৳1,850',
                'final_price' => '৳1,450',
                'has_discount' => true,
                'badge' => 'New',
                'is_saleable' => false,
            ]);
        @endphp

        <div class="gadget-product-grid">
            @foreach (array_slice($displayProducts, 0, 8) as $product)
                @include('shop::homepage.partials.product-card', ['product' => $product, 'mode' => 'latest', 'showAction' => true])
            @endforeach
        </div>
    </div>
</section>
