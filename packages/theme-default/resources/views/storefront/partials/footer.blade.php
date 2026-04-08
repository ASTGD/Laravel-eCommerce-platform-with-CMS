<footer class="storefront-footer">
    <div class="footer-grid">
        <div class="footer-copy">
            <span class="eyebrow">Default footer</span>
            <h2>{{ config('platform.brand_name') }}</h2>
            <p>{{ $footer?->settings_json['company_text'] ?? 'Reusable commerce platform.' }}</p>
        </div>

        <div>
            <h3>Navigate</h3>
            <ul class="footer-links">
                @foreach ($footerMenu?->items ?? [] as $item)
                    <li><a href="{{ $item->target }}">{{ $item->title }}</a></li>
                @endforeach
            </ul>
        </div>

        <div>
            <h3>{{ $footer?->settings_json['newsletter_heading'] ?? 'Updates' }}</h3>
            <p>Preset and section changes stay bounded inside the platform’s rendering contracts.</p>
        </div>
    </div>
</footer>
