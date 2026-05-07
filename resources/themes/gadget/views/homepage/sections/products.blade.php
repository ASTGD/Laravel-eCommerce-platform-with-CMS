<section class="gadget-section gadget-section--products" aria-labelledby="gadget-sale-products">
    <h2 id="gadget-sale-products" class="sr-only">Sale products</h2>

    <div class="gadget-container">
        <div class="gadget-product-grid gadget-product-grid--compact">
            @forelse ($products as $product)
                @include('shop::homepage.partials.product-card', ['product' => $product])
            @empty
                @for ($index = 0; $index < 4; $index++)
                    @include('shop::homepage.partials.product-card', [
                        'product' => [
                            'id' => 0,
                            'name' => 'SoundPEATS Cove Pro Over-Ear Headphones',
                            'short_name' => 'SoundPEATS Cove Pro Over-Ear Headphones',
                            'url' => '#',
                            'image' => bagisto_asset('images/medium-product-placeholder.webp', 'shop'),
                            'regular_price' => '$1,350.00',
                            'final_price' => '$1000.00',
                            'has_discount' => true,
                            'badge' => 'Sale',
                            'is_saleable' => false,
                        ],
                    ])
                @endfor
            @endforelse
        </div>

        <div class="gadget-center">
            <a href="{{ route('shop.search.index') }}" class="gadget-button gadget-button--dark">
                <span></span>
                Load More Products
            </a>
        </div>
    </div>
</section>
