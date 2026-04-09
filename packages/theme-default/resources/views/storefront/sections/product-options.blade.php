@php($attributes = $productData['super_attributes'])
@php($customizableOptions = $productData['customizable_options'])
<section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
    <h2 class="text-lg font-semibold text-slate-900">{{ $section['settings']['headline'] ?? 'Options' }}</h2>

    <div class="mt-4 space-y-4 text-sm text-slate-600">
        @if ($attributes->isNotEmpty())
            <div>
                <p class="font-medium text-slate-900">Variant Attributes</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach ($attributes as $attribute)
                        <span class="rounded-full bg-slate-100 px-3 py-1">{{ $attribute->admin_name ?: $attribute->code }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($customizableOptions->isNotEmpty())
            <div>
                <p class="font-medium text-slate-900">Customizable Options</p>
                <ul class="mt-2 space-y-1">
                    @foreach ($customizableOptions as $option)
                        <li>{{ $option->label }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($attributes->isEmpty() && $customizableOptions->isEmpty())
            <p>No additional options are required for this product.</p>
        @endif
    </div>
</section>
