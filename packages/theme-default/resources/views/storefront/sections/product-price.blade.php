<section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
    <p class="text-xs uppercase tracking-[0.2em] text-slate-400">{{ $section['settings']['headline'] ?? 'Price' }}</p>
    <div class="mt-4 text-2xl font-semibold text-slate-900">{!! $productData['price_html'] !!}</div>
</section>
