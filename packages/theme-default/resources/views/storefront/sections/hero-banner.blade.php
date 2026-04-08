<section class="section hero-banner">
    <div class="hero-copy">
        <span class="eyebrow">{{ $settings['eyebrow'] ?? '' }}</span>
        <h1>{{ $settings['headline'] ?? '' }}</h1>
        <p>{{ $settings['body'] ?? '' }}</p>

        <div class="hero-actions">
            @if (! empty($settings['primary_label']))
                <a href="{{ $settings['primary_url'] ?? '#' }}" class="button-link-solid">{{ $settings['primary_label'] }}</a>
            @endif

            @if (! empty($settings['secondary_label']))
                <a href="{{ $settings['secondary_url'] ?? '#' }}" class="button-link-outline">{{ $settings['secondary_label'] }}</a>
            @endif
        </div>
    </div>

    <div class="hero-image">
        @if (! empty($settings['image_url']))
            <img src="{{ $settings['image_url'] }}" alt="{{ $settings['headline'] ?? 'Hero image' }}">
        @endif
    </div>
</section>
