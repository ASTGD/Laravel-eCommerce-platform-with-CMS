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
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wdth,wght@12..96,75..100,400..800&family=Fraunces:opsz,wght,SOFT,WONK@9..144,500..900,40..100,1&display=swap');

        :root {
            --fashion-white: #fffdfa;
            --fashion-ink: #171114;
            --fashion-muted: #6f6468;
            --fashion-line: rgba(23, 17, 20, 0.12);
            --fashion-coral: #ff4f70;
            --fashion-lime: #c8ff4d;
            --fashion-violet: #7c5cff;
            --fashion-cyan: #55d7ff;
            --fashion-sun: #ffd166;
            --fashion-blush: #fff1f3;
            --fashion-cream: #fff8e8;
            --fashion-shadow: 0 28px 80px rgba(23, 17, 20, 0.10);
            --fashion-radius: 30px;
        }

        .gadget-home,
        .gadget-header,
        .gadget-footer {
            font-family: 'Bricolage Grotesque', ui-sans-serif, system-ui, sans-serif;
            color: var(--fashion-ink);
            background: var(--fashion-white);
        }

        .gadget-home * { box-sizing: border-box; }

        .gadget-container {
            width: min(1220px, calc(100% - 40px));
            margin-inline: auto;
        }

        .gadget-section { padding: clamp(64px, 8vw, 112px) 0; }

        .gadget-section-heading {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 24px;
            margin-bottom: 34px;
        }

        .gadget-section-heading h2 {
            font-family: 'Fraunces', serif;
            font-size: clamp(36px, 5vw, 68px);
            line-height: 0.95;
            letter-spacing: -0.055em;
            margin: 0;
            color: var(--fashion-ink);
        }

        .gadget-section-heading p {
            max-width: 520px;
            margin: 12px 0 0;
            color: var(--fashion-muted);
            font-size: 17px;
            line-height: 1.65;
        }

        .gadget-text-link,
        .btn-aura,
        .btn-order-now,
        .btn-cta-light,
        .fashion-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            border-radius: 999px;
            font-weight: 800;
            text-decoration: none !important;
            transition: transform .25s ease, box-shadow .25s ease, background .25s ease, color .25s ease;
            white-space: nowrap;
        }

        .gadget-text-link { color: var(--fashion-ink); }
        .gadget-text-link:hover { color: var(--fashion-coral); transform: translateX(4px); }

        .btn-aura,
        .fashion-button--dark {
            background: var(--fashion-ink);
            color: #fff !important;
            padding: 16px 26px;
            box-shadow: 0 14px 35px rgba(23, 17, 20, .20);
        }

        .btn-aura:hover,
        .fashion-button--dark:hover { transform: translateY(-3px); box-shadow: 0 20px 44px rgba(23, 17, 20, .24); }

        .fashion-button--color {
            background: linear-gradient(135deg, var(--fashion-coral), var(--fashion-violet));
            color: #fff !important;
            padding: 16px 26px;
            box-shadow: 0 16px 34px rgba(255, 79, 112, .22);
        }

        .fashion-button--soft {
            background: #fff;
            color: var(--fashion-ink) !important;
            border: 1px solid var(--fashion-line);
            padding: 15px 24px;
        }

        @media (max-width: 768px) {
            .gadget-container { width: min(100% - 28px, 1220px); }
            .gadget-section-heading { align-items: flex-start; flex-direction: column; }
        }
    </style>
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

    <div class="gadget-home fashion-home">
        @include('shop::homepage.sections.hero', ['products' => $homepage['latestProducts']])
        @include('shop::homepage.sections.promo-strip')
        @include('shop::homepage.sections.limited-sale')
        @include('shop::homepage.sections.products', ['products' => $homepage['saleProducts']])
        @include('shop::homepage.sections.categories', ['categories' => $homepage['categories']])
        @include('shop::homepage.sections.latest-products', ['products' => $homepage['latestProducts']])
        @include('shop::homepage.sections.cta', ['products' => $homepage['latestProducts']])
        @include('shop::homepage.sections.why-choose')
        @include('shop::homepage.sections.testimonials')
    </div>

    @include('shop::partials.gadget-footer')

    @pushOnce('scripts')
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('[data-gadget-add-to-cart]').forEach((button) => {
                    button.addEventListener('click', async () => {
                        const productId = button.dataset.productId;
                        const endpoint = button.dataset.endpoint;
                        const cartUrl = button.dataset.cartUrl;
                        const productUrl = button.dataset.productUrl;
                        const token = '{{ csrf_token() }}';

                        if (! productId || ! endpoint || ! token) {
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
                                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': token },
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
            });
        </script>
    @endPushOnce
</x-shop::layouts>
