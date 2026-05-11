@php
    $brandName = core()->getCurrentChannel()?->name ?? config('app.name');
    $columns = [
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
    ];
@endphp

@pushOnce('styles')
<style>
    .gadget-footer { background: #171114; color: #fffdfa; padding: 72px 0 34px; overflow: hidden; }
    .gadget-footer__inner { width: min(1220px, calc(100% - 40px)); margin: 0 auto; display: grid; grid-template-columns: 1.2fr .8fr; gap: 56px; position: relative; }
    .gadget-footer__inner::before { content: ''; position: absolute; width: 360px; height: 360px; right: -120px; top: -150px; background: radial-gradient(circle, rgba(255,79,112,.35), transparent 68%); filter: blur(8px); pointer-events: none; }
    .gadget-footer__brand a { color: #fff; text-decoration: none; display: inline-flex; align-items: center; gap: 14px; font-size: clamp(34px, 6vw, 74px); font-family: 'Fraunces', serif; font-weight: 850; letter-spacing: -0.06em; line-height: .9; }
    .gadget-footer__brand img { width: 54px; height: 54px; border-radius: 18px; object-fit: contain; background: #fff; }
    .gadget-footer__brand p { max-width: 540px; color: rgba(255,253,250,.72); font-size: 18px; line-height: 1.7; margin: 24px 0 28px; }
    .gadget-footer__social { display: flex; gap: 12px; }
    .gadget-footer__social a { width: 44px; height: 44px; border-radius: 50%; border: 1px solid rgba(255,255,255,.18); background: rgba(255,255,255,.06); display: grid; place-items: center; color: #fff; text-decoration: none; }
    .gadget-footer__social a:nth-child(1)::before { content: 'X'; font-weight: 900; }
    .gadget-footer__social a:nth-child(2)::before { content: 'IG'; font-weight: 900; font-size: 12px; }
    .gadget-footer__social a:nth-child(3)::before { content: 'FB'; font-weight: 900; font-size: 12px; }
    .gadget-footer__columns { display: grid; grid-template-columns: repeat(2, 1fr); gap: 28px; position: relative; z-index: 1; }
    .gadget-footer__columns h2 { font-size: 13px; text-transform: uppercase; letter-spacing: .18em; color: #c8ff4d; margin: 0 0 18px; }
    .gadget-footer__columns a { display: block; color: rgba(255,253,250,.75); text-decoration: none; font-weight: 700; padding: 9px 0; transition: color .25s ease, transform .25s ease; }
    .gadget-footer__columns a:hover { color: #fff; transform: translateX(4px); }
    @media (max-width: 800px) { .gadget-footer__inner { width: min(100% - 28px, 1220px); grid-template-columns: 1fr; } .gadget-footer__columns { grid-template-columns: 1fr; } }
</style>
@endPushOnce

<footer class="gadget-footer">
    <div class="gadget-footer__inner">
        <div class="gadget-footer__brand">
            <a href="{{ route('shop.home.index') }}" aria-label="{{ $brandName }}">
                <img src="{{ asset('images/astgd-ecommerce-logo.webp') }}" alt="" onerror="this.remove()">
                <span>{{ $brandName }}</span>
            </a>

            <p>Fresh silhouettes, expressive colors, everyday comfort, and fast local delivery for your next wardrobe mood.</p>

            <div class="gadget-footer__social" aria-label="Social links">
                <a href="#" aria-label="X"><span></span></a>
                <a href="#" aria-label="Instagram"><span></span></a>
                <a href="#" aria-label="Facebook"><span></span></a>
            </div>
        </div>

        <div class="gadget-footer__columns">
            @foreach ($columns as $column)
                <div>
                    <h2>{{ $column['title'] }}</h2>
                    @foreach ($column['links'] as $link)
                        <a href="{{ $link['url'] }}">{{ $link['label'] }}</a>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</footer>
