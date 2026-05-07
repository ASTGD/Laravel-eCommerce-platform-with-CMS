<section class="gadget-section gadget-latest" aria-labelledby="gadget-latest-title">
    <div class="gadget-container">
        <div class="gadget-section-heading gadget-section-heading--row">
            <div>
                <h2 id="gadget-latest-title">Latest Products</h2>
            </div>

            <a href="{{ route('shop.search.index') }}" class="gadget-text-link">
                Check All Products
                <span aria-hidden="true">-></span>
            </a>
        </div>

        <div class="gadget-divider"></div>

        <div class="gadget-product-grid gadget-product-grid--latest">
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
                            'name' => 'SoundPEATS Cove Pro Over-Ear Headphones',
                            'short_name' => 'SoundPEATS Cove Pro Over-Ear Headphones',
                            'url' => '#',
                            'image' => bagisto_asset('images/medium-product-placeholder.webp', 'shop'),
                            'regular_price' => '$1,350.00',
                            'final_price' => '$1000.00',
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
