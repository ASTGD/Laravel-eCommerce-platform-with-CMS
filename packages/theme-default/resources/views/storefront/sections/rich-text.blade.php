<section class="section text-section">
    <div class="section-heading">
        <span class="eyebrow">{{ $settings['eyebrow'] ?? '' }}</span>
        <h2>{{ $settings['headline'] ?? '' }}</h2>
    </div>

    <div class="rich-text">
        {!! $settings['body'] ?? '' !!}
    </div>
</section>
