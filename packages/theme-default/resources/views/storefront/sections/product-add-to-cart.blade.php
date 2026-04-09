@php($product = $context['product'])
@php($isDirectCartType = in_array($product->type, ['simple', 'virtual', 'downloadable'], true))
<section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
    <h2 class="text-lg font-semibold text-slate-900">{{ $section['settings']['button_label'] ?? 'Add to cart' }}</h2>

    @if ($isDirectCartType)
        <form method="POST" action="{{ route('shop.api.checkout.cart.store') }}" class="mt-4 flex flex-wrap items-center gap-3" data-add-to-cart-form="section-{{ $section['id'] }}">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <input type="number" min="1" name="quantity" value="{{ $section['settings']['default_quantity'] ?? 1 }}" class="w-24 rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <button type="submit" class="rounded-full bg-slate-900 px-5 py-3 text-sm font-medium text-white">
                {{ $section['settings']['button_label'] ?? 'Add to cart' }}
            </button>
        </form>
        <p class="mt-3 text-sm text-slate-500" data-add-to-cart-message="section-{{ $section['id'] }}"></p>

        <script>
            (() => {
                const form = document.querySelector('[data-add-to-cart-form="section-{{ $section['id'] }}"]');
                const message = document.querySelector('[data-add-to-cart-message="section-{{ $section['id'] }}"]');

                if (! form) {
                    return;
                }

                form.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    const formData = new FormData(form);

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                            },
                            body: formData,
                        });

                        const payload = await response.json();

                        if (! response.ok) {
                            message.textContent = payload.message || 'Unable to add this product to cart.';
                            return;
                        }

                        window.location.href = '{{ route('shop.checkout.cart.index') }}';
                    } catch (error) {
                        message.textContent = 'Unable to add this product to cart.';
                    }
                });
            })();
        </script>
    @else
        <p class="mt-4 text-sm text-slate-600">This product type requires richer option handling. The future frontend implementation can use the stable PDP payload already exposed here.</p>
    @endif
</section>
