@php
    $settings = $section['settings'] ?? [];
    $slides = collect($settings['slides'] ?? [])
        ->filter(fn ($slide) => ! empty($slide['image']))
        ->take(5)
        ->values();
@endphp

@if ($slides->isNotEmpty())
    <section class="bg-white" aria-label="{{ $section['title'] ?: 'Hero' }}">
        <div class="mx-auto grid max-w-7xl grid-cols-1 items-center gap-10 px-6 py-16 lg:grid-cols-2 lg:px-8">
            <div>
                <h1 class="font-sans text-4xl font-bold tracking-tight text-slate-950 md:text-5xl">
                    {{ $slides->first()['headline'] ?: ($slides->first()['title'] ?: ($section['title'] ?: 'Hero')) }}
                </h1>

                @if (! empty($slides->first()['body']))
                    <p class="mt-5 max-w-xl text-base leading-7 text-slate-600">
                        {{ $slides->first()['body'] }}
                    </p>
                @endif

                <div class="mt-8 flex flex-wrap gap-3">
                    @if (! empty($slides->first()['primary_cta_label']))
                        <a href="{{ $slides->first()['primary_cta_url'] ?: '#' }}" class="inline-flex rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white">
                            {{ $slides->first()['primary_cta_label'] }}
                        </a>
                    @endif

                    @if (! empty($slides->first()['secondary_cta_label']))
                        <a href="{{ $slides->first()['secondary_cta_url'] ?: '#' }}" class="inline-flex rounded-xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700">
                            {{ $slides->first()['secondary_cta_label'] }}
                        </a>
                    @endif
                </div>
            </div>

            <img
                src="{{ \Illuminate\Support\Str::startsWith($slides->first()['image'], ['http://', 'https://', '/']) ? $slides->first()['image'] : asset($slides->first()['image']) }}"
                alt="{{ $slides->first()['title'] ?: $section['title'] ?: 'Hero image' }}"
                class="aspect-[4/3] w-full rounded-[24px] object-cover"
            >
        </div>
    </section>
@endif
