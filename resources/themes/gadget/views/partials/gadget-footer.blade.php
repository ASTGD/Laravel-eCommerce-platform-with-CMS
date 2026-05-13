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
