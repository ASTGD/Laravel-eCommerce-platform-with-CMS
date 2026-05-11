@php
    $settings = $section['settings'] ?? [];
    $slides = collect($settings['slides'] ?? [])
        ->filter(fn ($slide) => ! empty($slide['image']))
        ->map(fn ($slide) => [
            'image' => $slide['image'],
            'link' => $slide['link'] ?? null,
            'title' => $slide['title'] ?? 'Hero slide',
        ])
        ->values()
        ->all();
@endphp

@if (! empty($slides))
    <section aria-label="{{ $section['title'] ?: 'Hero slider' }}">
        <x-shop::carousel
            :options="['images' => $slides]"
            aria-label="{{ $section['title'] ?: 'Hero slider' }}"
        />
    </section>
@endif
