@php($resource = $productData['resource'])
<section class="rounded-[2rem] bg-white p-8 shadow-sm ring-1 ring-slate-200">
    <p class="text-xs uppercase tracking-[0.25em] text-slate-400">{{ strtoupper($product->type) }}</p>
    <h1 class="mt-3 text-3xl font-semibold text-slate-900">{{ $product->name }}</h1>

    @if ($section['settings']['show_sku'] ?? true)
        <p class="mt-3 text-sm text-slate-500">SKU: {{ $product->sku }}</p>
    @endif

    <div class="prose mt-5 max-w-none text-slate-600">
        {!! $product->short_description ?: \Illuminate\Support\Str::limit(strip_tags($product->description), 220) !!}
    </div>
</section>
