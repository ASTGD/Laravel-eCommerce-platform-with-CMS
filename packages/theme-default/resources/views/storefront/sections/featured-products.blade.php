<section class="section product-section" id="featured-products">
    <div class="section-heading">
        <span class="eyebrow">Merchandising section</span>
        <h2>{{ $settings['headline'] ?? '' }}</h2>
        <p>{{ $settings['body'] ?? '' }}</p>
    </div>

    <div class="product-grid">
        @foreach ($items as $item)
            <article class="product-card">
                @if (! empty($item['badge']))
                    <span class="product-badge">{{ $item['badge'] }}</span>
                @endif
                <h3>{{ $item['title'] ?? 'Product' }}</h3>
                <p>{{ $item['subtitle'] ?? '' }}</p>
                <strong>{{ $item['price'] ?? '' }}</strong>
                <a href="{{ $item['url'] ?? '#' }}">View product</a>
            </article>
        @endforeach
    </div>
</section>
