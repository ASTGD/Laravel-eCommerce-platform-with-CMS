@php($items = $section['items'] ?? collect())
<section class="mx-auto max-w-6xl px-6 py-14">
    <div class="flex items-end justify-between gap-6">
        <div>
            <p class="text-xs uppercase tracking-[0.35em] text-slate-500">{{ $section['settings']['eyebrow'] ?? 'Featured Products' }}</p>
            <h2 class="mt-2 text-3xl font-semibold text-slate-900">{{ $section['title'] ?? 'Featured products' }}</h2>
        </div>
    </div>

    <div class="mt-8 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
        @forelse ($items as $item)
            <article class="rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Catalog</p>
                <h3 class="mt-3 text-lg font-semibold text-slate-900">{{ $item->name ?? $item->sku ?? 'Product' }}</h3>
                <p class="mt-2 text-sm text-slate-500">{{ $item->sku ?? 'SKU unavailable' }}</p>
            </article>
        @empty
            <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-100 p-6 text-sm text-slate-500 md:col-span-2 xl:col-span-4">
                No products resolved for this section yet. Once the catalog is seeded, this section will render live commerce data.
            </div>
        @endforelse
    </div>
</section>
