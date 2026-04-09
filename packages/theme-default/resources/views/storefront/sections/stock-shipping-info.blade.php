<section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
    <h2 class="text-lg font-semibold text-slate-900">Stock & Shipping</h2>

    <div class="mt-4 space-y-2 text-sm text-slate-600">
        <p>Saleable: {{ $productData['resource']['is_saleable'] ? 'Yes' : 'No' }}</p>
        <p>{{ $section['settings']['shipping_note'] ?? $productData['shipping_note'] }}</p>
    </div>
</section>
