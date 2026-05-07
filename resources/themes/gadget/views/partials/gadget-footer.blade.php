@php
    $brandName = core()->getCurrentChannel()?->name ?? config('app.name');
    $columns = [
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
    ];
@endphp

<footer class="gadget-footer">
    <div class="gadget-footer__inner">
        <div class="gadget-footer__brand">
            <a href="{{ route('shop.home.index') }}" aria-label="{{ $brandName }}">
                <img src="{{ asset('images/astgd-ecommerce-logo.webp') }}" alt="" onerror="this.remove()">
                <span>{{ $brandName }}</span>
            </a>

            <p>
                Curated smart gadgets, simple ordering, secure checkout, and fast local delivery.
            </p>

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
