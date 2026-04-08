<section class="section generic-section">
    <div class="section-heading">
        <span class="eyebrow">{{ $definition?->name() ?? 'Section' }}</span>
        <h2>{{ $settings['headline'] ?? $settings['message'] ?? ($section->title ?: 'Section preview') }}</h2>
        <p>This section type is registered and editable. A dedicated storefront renderer can be added without changing the core page composition contract.</p>
    </div>
</section>
