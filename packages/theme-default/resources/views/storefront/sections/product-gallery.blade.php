@php($gallery = $productData['gallery'])
<section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
    <p class="text-xs uppercase tracking-[0.2em] text-slate-400">{{ $section['settings']['headline'] ?? 'Gallery' }}</p>

    <div class="mt-4 grid gap-4 md:grid-cols-2">
        @forelse ($gallery as $image)
            <div class="overflow-hidden rounded-[1.5rem] bg-slate-100 p-3">
                <img src="{{ $image['large_image_url'] ?? $image['medium_image_url'] ?? $image['small_image_url'] ?? '' }}" alt="{{ $product->name }}" class="h-full w-full rounded-[1rem] object-cover">
            </div>
        @empty
            <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-100 p-6 text-sm text-slate-500">No gallery images were available for this product.</div>
        @endforelse
    </div>
</section>
