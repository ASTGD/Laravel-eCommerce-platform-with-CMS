@php
$channel = core()->getCurrentChannel();
$homepage = app(\Platform\ThemeDefault\ViewModels\StorefrontHomepageViewModel::class)->build();
@endphp

@push('meta')
<meta name="title" content="{{ $channel->home_seo['meta_title'] ?? '' }}">
<meta name="description" content="{{ $channel->home_seo['meta_description'] ?? '' }}">
<meta name="keywords" content="{{ $channel->home_seo['meta_keywords'] ?? '' }}">
@endpush

@push('styles')
<link rel="stylesheet" href="{{ asset('themes/shop/gadget/gadget.css') }}">
@endpush

@push('scripts')
@if (! empty($categories))
<script>
    localStorage.setItem('categories', JSON.stringify(@json($categories)));
</script>
@endif
@endpush

<x-shop::layouts :has-header="false" :has-feature="false" :has-footer="false">
    <x-slot:title>
        {{ $channel->home_seo['meta_title'] ?? 'Homepage' }}
        </x-slot>

        @include('shop::partials.gadget-header')

        <div class="gadget-home">
            @if (! empty($homepage['heroSliderImages']))
            <x-shop::carousel
                :options="['images' => $homepage['heroSliderImages']]"
                aria-label="Hero slider" />
            @else
            @include('shop::homepage.sections.hero', ['products' => $homepage['latestProducts']])
            @endif

            @include('shop::homepage.sections.promo-strip')
            @include('shop::homepage.sections.limited-sale', ['products' => $homepage['saleProducts']])
            @include('shop::homepage.sections.categories', ['categories' => $homepage['categories']])
            @include('shop::homepage.sections.products', ['products' => $homepage['featuredPicks']])
            @include('shop::homepage.sections.latest-products', ['products' => $homepage['latestProducts']])
            @include('shop::homepage.sections.cta', ['products' => $homepage['latestProducts']])
            @include('shop::homepage.sections.why-choose', ['products' => $homepage['personalizedPicks']])
            @include('shop::homepage.sections.testimonials')
        </div>

        @include('shop::partials.gadget-footer')

        @pushOnce('scripts')
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                document.body.addEventListener('click', async (e) => {
                    const button = e.target.closest('[data-gadget-add-to-cart]');
                    if (!button) return;
                    
                    e.preventDefault();

                    const productId = button.dataset.productId;
                    const endpoint = button.dataset.endpoint;
                    const cartUrl = button.dataset.cartUrl;
                    const productUrl = button.dataset.productUrl;
                    const token = '{{ csrf_token() }}';

                    if (!productId || !endpoint || !token) {
                        window.location.href = productUrl || cartUrl || '/';
                        return;
                    }

                    button.disabled = true;

                    const formData = new FormData();
                    formData.append('product_id', productId);
                    formData.append('quantity', '1');

                    try {
                        const response = await fetch(endpoint, {
                            method: 'POST',
                            headers: {
                                Accept: 'application/json',
                                'X-CSRF-TOKEN': token,
                            },
                            body: formData,
                        });

                        const payload = await response.json();

                        if (response.ok) {
                            window.location.href = cartUrl;
                            return;
                        }

                        window.location.href = payload.redirect_uri || payload.data || productUrl || cartUrl;
                    } catch (error) {
                        window.location.href = productUrl || cartUrl || '/';
                    } finally {
                        button.disabled = false;
                    }
                });
            });
        </script>
        @endPushOnce
</x-shop::layouts>