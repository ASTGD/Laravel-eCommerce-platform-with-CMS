@php($details = $productData['details'])
<section class="rounded-[2rem] bg-white p-8 shadow-sm ring-1 ring-slate-200">
    <h2 class="text-2xl font-semibold text-slate-900">{{ $section['settings']['headline'] ?? 'Product Details' }}</h2>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        @forelse ($details as $detail)
            <div class="rounded-xl border border-slate-200 p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-slate-400">{{ $detail['label'] }}</p>
                <p class="mt-2 text-sm text-slate-700">{{ $detail['value'] }}</p>
            </div>
        @empty
            <p class="text-sm text-slate-500">No structured product details were available for this item.</p>
        @endforelse
    </div>
</section>
