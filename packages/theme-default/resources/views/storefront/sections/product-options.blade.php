@php($configurableConfig = $productData['configurable_config'] ?? [])
@php($attributes = collect($configurableConfig['attributes'] ?? []))

<section
    class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200"
    data-configurable-options="product-{{ $product->id }}"
>
    <h2 class="text-lg font-semibold text-slate-900">{{ $section['settings']['headline'] ?? 'Options' }}</h2>

    @if ($attributes->isNotEmpty())
        <p class="mt-2 text-sm text-slate-500">Choose the shirt size and color before adding it to the cart.</p>

        <div class="mt-4 space-y-5">
            @foreach ($attributes as $attribute)
                <div class="space-y-2">
                    <label for="configurable-{{ $product->id }}-{{ $attribute['id'] }}" class="block text-sm font-medium text-slate-700">
                        {{ $attribute['label'] }}
                    </label>

                    <select
                        id="configurable-{{ $product->id }}-{{ $attribute['id'] }}"
                        class="w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-200"
                        data-configurable-attribute="{{ $attribute['id'] }}"
                    >
                        <option value="">Select {{ $attribute['label'] }}</option>

                        @foreach ($attribute['options'] as $option)
                            <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            @endforeach
        </div>

        <p class="mt-4 text-sm text-slate-500" data-configurable-selection-message>
            Select all options to unlock the add-to-cart button.
        </p>
    @else
        @if (($productData['super_attributes'] ?? collect())->isNotEmpty())
            <div class="mt-4 space-y-3 text-sm text-slate-600">
                <p class="font-medium text-slate-900">Variant Attributes</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($productData['super_attributes'] as $attribute)
                        <span class="rounded-full bg-slate-100 px-3 py-1">{{ $attribute->admin_name ?: $attribute->code }}</span>
                    @endforeach
                </div>
            </div>
        @elseif (($productData['customizable_options'] ?? collect())->isNotEmpty())
            <div class="mt-4 space-y-3 text-sm text-slate-600">
                <p class="font-medium text-slate-900">Customizable Options</p>
                <ul class="space-y-1">
                    @foreach ($productData['customizable_options'] as $option)
                        <li>{{ $option->label }}</li>
                    @endforeach
                </ul>
            </div>
        @else
            <p class="mt-4 text-sm text-slate-600">No additional options are required for this product.</p>
        @endif
    @endif

    @if ($attributes->isNotEmpty())
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                const root = document.querySelector('[data-configurable-options="product-{{ $product->id }}"]');
                const cartForm = document.querySelector('[data-configurable-cart-form="product-{{ $product->id }}"]');
                const selectionMessage = root?.querySelector('[data-configurable-selection-message]');
                const priceTarget = document.querySelector('[data-product-price="product-{{ $product->id }}"]');
                const selects = Array.from(root?.querySelectorAll('[data-configurable-attribute]') || []);
                const config = @json($configurableConfig);
                const variantPrices = config.variant_prices || {};
                const index = config.index || {};

                if (! root || ! cartForm || ! selects.length) {
                    return;
                }

                const selectedOptionInput = cartForm.querySelector('[data-configurable-selection-input]');
                const submitButton = cartForm.querySelector('[data-configurable-submit-button]');

                const updateState = () => {
                    const selectedValues = {};

                    for (const select of selects) {
                        if (! select.value) {
                            selectedOptionInput.value = '';

                            if (submitButton) {
                                submitButton.disabled = true;
                            }

                            if (selectionMessage) {
                                selectionMessage.textContent = 'Select all options to unlock the add-to-cart button.';
                            }

                            return;
                        }

                        selectedValues[select.dataset.configurableAttribute] = select.value;
                    }

                    const matchedVariant = Object.entries(index).find(([, optionMap]) => {
                        return Object.entries(selectedValues).every(([attributeId, optionId]) => {
                            return String(optionMap[attributeId]) === String(optionId);
                        });
                    });

                    const selectedVariantId = matchedVariant ? matchedVariant[0] : '';

                    selectedOptionInput.value = selectedVariantId;

                    if (submitButton) {
                        submitButton.disabled = ! selectedVariantId;
                    }

                    if (selectionMessage) {
                        selectionMessage.textContent = selectedVariantId
                            ? 'Variant selected. You can add it to the cart now.'
                            : 'No matching variant found for this combination.';
                    }

                    if (selectedVariantId && priceTarget && variantPrices[selectedVariantId]) {
                        const variantPrice = variantPrices[selectedVariantId];

                        priceTarget.innerHTML = variantPrice.final?.formatted_price ?? variantPrice.regular?.formatted_price ?? priceTarget.innerHTML;
                    }
                };

                selects.forEach((select) => select.addEventListener('change', updateState));
                updateState();
            });
        </script>
    @endif
</section>
