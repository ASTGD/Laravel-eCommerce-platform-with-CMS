@php
    $footerView = app(\Platform\ThemeDefault\ViewModels\StorefrontFooterViewModel::class)->build([
        'description' => 'Curated smart gadgets, simple ordering, secure checkout, and fast local delivery.',
        'columns' => [
            [
                'title' => 'Company',
                'links' => [
                    ['label' => 'About Us', 'url' => route('shop.cms.page', 'about-us')],
                    ['label' => 'Products', 'url' => route('shop.search.index')],
                    ['label' => 'Contact', 'url' => route('shop.home.contact_us')],
                ],
            ],
            [
                'title' => 'Support',
                'links' => [
                    ['label' => 'Track Shipment', 'url' => route('shop.shipment-tracking.index')],
                    ['label' => 'Privacy Policy', 'url' => route('shop.cms.page', 'privacy-policy')],
                    ['label' => 'Terms of Service', 'url' => route('shop.cms.page', 'terms-of-service')],
                ],
            ],
        ],
        'socialLinks' => [
            ['label' => 'X', 'short_label' => 'X', 'url' => '#'],
            ['label' => 'Instagram', 'short_label' => 'IG', 'url' => '#'],
            ['label' => 'Facebook', 'short_label' => 'FB', 'url' => '#'],
        ],
    ]);
@endphp

@pushOnce('styles')
<style>
    .gadget-footer {
        background: linear-gradient(135deg, #0f172a 0%, #2e1065 100%) !important; /* Premium Blue to Purple dark gradient */
        color: #cbd5e1;
        padding: 80px 0 40px;
        position: relative;
        overflow: hidden;
    }

    .gadget-footer::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.2), transparent);
    }

    .gadget-footer__inner {
        max-width: 1600px !important;
        margin: 0 auto;
        padding: 0 40px;
    }

    .gadget-footer__legal {
        border-top: 1px solid rgba(255, 255, 255, 0.05);
        margin-top: 60px;
        padding-top: 30px;
        color: #64748b;
        text-align: center;
        font-size: 14px;
    }

    .gadget-footer a {
        color: #94a3b8;
        transition: color 0.3s ease;
        text-decoration: none;
    }

    .gadget-footer a:hover {
        color: #3b82f6;
    }

    .gadget-footer h2 {
        color: #ffffff;
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 24px;
        text-transform: uppercase;
        letter-spacing: 0.1em;
    }

    .gadget-footer__newsletter {
        background: rgba(255, 255, 255, 0.02) !important;
        border: 1px solid rgba(255, 255, 255, 0.05) !important;
        border-radius: 20px;
        padding: 30px;
        margin-top: 60px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 30px;
    }

    .gadget-footer__newsletter input {
        background: rgba(255, 255, 255, 0.05) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 12px;
        color: #ffffff !important;
        padding: 12px 20px;
        width: 100%;
        max-width: 400px;
    }

    .gadget-footer__newsletter button {
        background: #3b82f6 !important;
        color: #ffffff !important;
        border: none;
        border-radius: 12px;
        padding: 12px 30px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.3s ease;
    }

    .gadget-footer__newsletter button:hover {
        background: #2563eb !important;
    }
</style>
@endpushOnce

<footer class="gadget-footer">
    <div class="gadget-footer__inner">
        <div class="gadget-footer__top">
            <div class="gadget-footer__brand">
                <a href="{{ $footerView['homeUrl'] }}" aria-label="{{ $footerView['brandName'] }}">
                    @if (filled($footerView['logoUrl']))
                        <img src="{{ $footerView['logoUrl'] }}" alt="" onerror="this.remove()">
                    @else
                        <span>{{ $footerView['brandName'] }}</span>
                    @endif
                </a>

                @if (filled($footerView['description']))
                    <p>{{ $footerView['description'] }}</p>
                @endif

                @if (filled($footerView['contact']['email']) || filled($footerView['contact']['phone']))
                    <div class="gadget-footer__contact">
                        @if (filled($footerView['contact']['email']))
                            <a href="mailto:{{ $footerView['contact']['email'] }}">{{ $footerView['contact']['email'] }}</a>
                        @endif

                        @if (filled($footerView['contact']['phone']))
                            <a href="tel:{{ $footerView['contact']['phone'] }}">{{ $footerView['contact']['phone'] }}</a>
                        @endif
                    </div>
                @endif

                @if (! empty($footerView['socialLinks']))
                    <div class="gadget-footer__social" aria-label="Social links">
                        @foreach ($footerView['socialLinks'] as $link)
                            <a href="{{ $link['url'] }}" aria-label="{{ $link['label'] }}">
                                @include('theme-default::storefront.partials.social-icon', ['icon' => $link['icon'] ?? 'link'])
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="gadget-footer__columns">
                @foreach ($footerView['columns'] as $column)
                    <div>
                        <h2>{{ $column['title'] }}</h2>

                        @foreach ($column['links'] as $link)
                            <a
                                href="{{ $link['url'] }}"
                                @if ($link['open_in_new_tab']) target="_blank" rel="noopener noreferrer" @endif
                            >
                                {{ $link['label'] }}
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>

        @if ($footerView['newsletter']['enabled'])
            <form
                method="POST"
                action="{{ route('shop.subscription.store') }}"
                class="gadget-footer__newsletter"
            >
                @csrf

                <div class="gadget-footer__newsletter-copy">
                    <strong>{{ $footerView['newsletter']['heading'] }}</strong>

                    <span>{{ $footerView['newsletter']['text'] }}</span>
                </div>

                <div class="gadget-footer__newsletter-form">
                    <label class="gadget-footer__visually-hidden" for="gadget-footer-newsletter-email">Email address</label>
                    <input
                        id="gadget-footer-newsletter-email"
                        type="email"
                        name="email"
                        required
                        placeholder="email@example.com"
                    >

                    <button type="submit">Subscribe</button>
                </div>

                @error('email')
                    <small>{{ $message }}</small>
                @enderror
            </form>
        @endif
    </div>

    <p class="gadget-footer__legal">{{ $footerView['copyrightText'] }}</p>
</footer>
