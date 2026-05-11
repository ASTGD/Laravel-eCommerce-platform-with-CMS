@pushOnce('styles')
<style>
    .gadget-section {
        padding: 100px 0;
        background: #ffffff;
    }

    .gadget-section-heading {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: 40px;
    }

    .gadget-section-heading h2 {
        font-size: 40px;
        font-weight: 900;
        color: #0f172a;
        letter-spacing: -0.04em;
    }

    .gadget-text-link {
        color: #3b82f6;
        text-decoration: none;
        font-weight: 700;
        font-size: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: 0.3s;
    }

    .gadget-text-link:hover {
        gap: 12px;
        color: #1e40af;
    }

    .gadget-product-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 30px;
    }

    @media (max-width: 1200px) {
        .gadget-product-grid { grid-template-columns: repeat(3, 1fr); }
    }

    @media (max-width: 991px) {
        .gadget-product-grid { grid-template-columns: repeat(2, 1fr); }
        .gadget-section-heading h2 { font-size: 32px; }
    }

    @media (max-width: 480px) {
        .gadget-product-grid { grid-template-columns: 1fr; }
    }
</style>
@endpushOnce

<section class="gadget-section gadget-latest" aria-labelledby="gadget-latest-title">
    <div class="gadget-container">
        <div class="gadget-section-heading">
            <div>
                <h2 id="gadget-latest-title">New Arrivals</h2>
            </div>

            <a href="{{ route('shop.search.index') }}" class="gadget-text-link">
                View All
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
            </a>
        </div>

        <div class="gadget-product-grid">
            @forelse ($products as $product)
                @include('shop::homepage.partials.product-card', [
                    'product' => $product,
                    'mode' => 'latest',
                    'showAction' => true,
                ])
            @empty
                @for ($index = 0; $index < 4; $index++)
                    @include('shop::homepage.partials.product-card', [
                        'mode' => 'latest',
                        'showAction' => false,
                        'product' => [
                            'id' => 0,
                            'name' => 'Premium Gadget Sample',
                            'short_name' => 'Premium Gadget Sample',
                            'url' => '#',
                            'image' => bagisto_asset('images/medium-product-placeholder.webp', 'shop'),
                            'regular_price' => '$1,350.00',
                            'final_price' => '$1,000.00',
                            'has_discount' => true,
                            'badge' => 'New',
                            'is_saleable' => false,
                        ],
                    ])
                @endfor
            @endforelse
        </div>
    </div>
</section>
