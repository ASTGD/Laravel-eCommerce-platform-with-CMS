@php
    $footerView = app(\Platform\ThemeDefault\ViewModels\StorefrontFooterViewModel::class)->build([
        'description' => 'Fresh silhouettes, expressive colors, everyday comfort, and fast local delivery for your next wardrobe mood.',
        'columns' => [
            [
                'title' => 'Studio',
                'links' => [
                    ['label' => 'About Us', 'url' => route('shop.cms.page', 'about-us')],
                    ['label' => 'All Looks', 'url' => route('shop.search.index')],
                    ['label' => 'Contact', 'url' => route('shop.home.contact_us')],
                ],
            ],
            [
                'title' => 'Customer Care',
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
    .gadget-footer { background: #171114; color: #fffdfa; padding: 72px 0 34px; overflow: hidden; }
    .gadget-footer__inner { width: min(1220px, calc(100% - 40px)); margin: 0 auto; display: grid; gap: 44px; position: relative; }
    .gadget-footer__inner::before { content: ''; position: absolute; width: 360px; height: 360px; right: -120px; top: -150px; background: radial-gradient(circle, rgba(255,79,112,.35), transparent 68%); filter: blur(8px); pointer-events: none; }
    .gadget-footer__top { display: grid; grid-template-columns: minmax(260px, .36fr) minmax(0, 1fr); gap: 72px; align-items: start; position: relative; z-index: 1; }
    .gadget-footer__brand a { color: #fff; text-decoration: none; display: inline-flex; align-items: center; gap: 14px; font-size: clamp(34px, 6vw, 74px); font-family: 'Fraunces', serif; font-weight: 850; letter-spacing: -0.06em; line-height: .9; }
    .gadget-footer__brand img { max-width: 180px; max-height: 56px; object-fit: contain; }
    .gadget-footer__brand p { max-width: 540px; color: rgba(255,253,250,.72); font-size: 18px; line-height: 1.7; margin: 24px 0 28px; }
    .gadget-footer__visually-hidden { position: absolute; width: 1px; height: 1px; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; padding: 0; }
    .gadget-footer__contact { display: grid; gap: 8px; margin: 0 0 28px; }
    .gadget-footer__contact a { color: rgba(255,253,250,.75); font-size: 15px; font-weight: 800; text-decoration: none; }
    .gadget-footer__social { display: flex; gap: 12px; }
    .gadget-footer__social a { width: 44px; height: 44px; border-radius: 50%; border: 1px solid rgba(255,255,255,.18); background: rgba(255,255,255,.06); display: grid; place-items: center; color: #fff; text-decoration: none; }
    .gadget-footer__social-icon { width: 18px; height: 18px; display: block; }
    .gadget-footer__newsletter { display: grid; grid-template-columns: minmax(0, 1fr) minmax(360px, 520px); align-items: center; gap: 28px; border: 1px solid rgba(255,255,255,.14); border-radius: 20px; background: rgba(255,255,255,.06); padding: 22px 24px; position: relative; z-index: 1; }
    .gadget-footer__newsletter-copy { display: grid; gap: 8px; }
    .gadget-footer__newsletter strong { font-size: 18px; font-weight: 900; }
    .gadget-footer__newsletter span { color: rgba(255,253,250,.72); font-size: 14px; line-height: 1.5; }
    .gadget-footer__newsletter-form { display: flex; gap: 10px; }
    .gadget-footer__newsletter input { min-width: 0; flex: 1; border: 1px solid rgba(255,255,255,.16); border-radius: 999px; background: rgba(255,255,255,.08); color: #fffdfa; padding: 13px 16px; font: inherit; outline: none; }
    .gadget-footer__newsletter input::placeholder { color: rgba(255,253,250,.5); }
    .gadget-footer__newsletter button { border: 0; border-radius: 999px; background: #c8ff4d; color: #171114; padding: 0 18px; font-weight: 900; cursor: pointer; }
    .gadget-footer__newsletter small { color: #fecdd3; font-size: 13px; font-weight: 700; }
    .gadget-footer__columns { display: grid; grid-template-columns: repeat(auto-fit, minmax(128px, 1fr)); gap: 34px 40px; justify-items: end; text-align: right; position: relative; z-index: 1; }
    .gadget-footer__columns h2 { font-size: 12px; line-height: 1.5; text-transform: uppercase; letter-spacing: .18em; color: #c8ff4d; margin: 0 0 18px; font-weight: 700; }
    .gadget-footer__columns a { display: block; color: rgba(255,253,250,.75); text-decoration: none; font-size: 14px; line-height: 1.65; font-weight: 400; padding: 6px 0; transition: color .25s ease, transform .25s ease; }
    .gadget-footer__columns a:hover { color: #fff; transform: translateX(4px); }
    .gadget-footer__legal { width: min(1220px, calc(100% - 40px)); margin: 42px auto 0; border-top: 1px solid rgba(255,255,255,.12); color: rgba(255,253,250,.55); font-size: 14px; font-weight: 700; padding-top: 24px; }
    @media (max-width: 900px) { .gadget-footer__inner { width: min(100% - 28px, 1220px); } .gadget-footer__top, .gadget-footer__newsletter { grid-template-columns: 1fr; } .gadget-footer__columns { grid-template-columns: repeat(2, minmax(0, 1fr)); } .gadget-footer__newsletter-form { flex-direction: column; } .gadget-footer__newsletter button { min-height: 46px; } }
    @media (max-width: 560px) { .gadget-footer__columns { grid-template-columns: 1fr; } }
</style>
@endPushOnce

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
                    <label class="gadget-footer__visually-hidden" for="clothing-footer-newsletter-email">Email address</label>
                    <input
                        id="clothing-footer-newsletter-email"
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
