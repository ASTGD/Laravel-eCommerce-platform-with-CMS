@php
    $mode = $section['settings']['mode'] ?? 'related';
    $items = $mode === 'up_sell' ? ($productData['up_sell_products'] ?? collect()) : ($productData['related_products'] ?? collect());
@endphp
<section class="rounded-[2rem] bg-white p-8 shadow-sm ring-1 ring-slate-200">
    <h2 class="text-2xl font-semibold text-slate-900">{{ $section['settings']['headline'] ?? 'You may also like' }}</h2>

    <div class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
        @forelse ($items as $item)
            <article class="rounded-[1.5rem] border border-slate-200 bg-white p-5">
                <p class="text-xs uppercase tracking-[0.2em] text-slate-400">{{ $item['sku'] ?? 'SKU' }}</p>
                <h3 class="mt-3 text-lg font-semibold text-slate-900">
                    <a href="{{ route('shop.product_or_category.index', $item['url_key']) }}">{{ $item['name'] }}</a>
                </h3>
                <div class="mt-3 text-sm text-slate-600">{!! $item['price_html'] !!}</div>
            </article>
        @empty
            <p class="text-sm text-slate-500">No related products were available for this item.</p>
        @endforelse
    </div>
</section>
