@php($settings = $section['settings'])
<section class="bg-slate-950 text-white">
    <div class="mx-auto grid max-w-6xl gap-10 px-6 py-20 md:grid-cols-[1.2fr_0.8fr] md:items-center">
        <div>
            <p class="text-xs uppercase tracking-[0.35em] text-orange-300">{{ $settings['eyebrow'] ?? 'Structured Commerce' }}</p>
            <h1 class="mt-4 max-w-3xl text-4xl font-semibold leading-tight md:text-6xl">
                {{ $settings['headline'] ?? $page->title }}
            </h1>
            <p class="mt-6 max-w-2xl text-base text-slate-300 md:text-lg">
                {{ $settings['body'] ?? 'Launch repeatable storefront experiences with a structured CMS and configuration-driven theme system.' }}
            </p>
            <div class="mt-8 flex flex-wrap gap-4">
                <a href="{{ $settings['primary_cta_url'] ?? '#' }}" class="rounded-full bg-orange-500 px-5 py-3 text-sm font-medium text-white">
                    {{ $settings['primary_cta_label'] ?? 'Shop now' }}
                </a>
                <a href="{{ $settings['secondary_cta_url'] ?? '#' }}" class="rounded-full border border-white/30 px-5 py-3 text-sm font-medium text-white">
                    {{ $settings['secondary_cta_label'] ?? 'Learn more' }}
                </a>
            </div>
        </div>

        <div class="rounded-[2rem] border border-white/10 bg-white/5 p-8 shadow-2xl">
            <div class="rounded-[1.5rem] bg-gradient-to-br from-orange-400 via-amber-300 to-white p-10 text-slate-950">
                <p class="text-sm uppercase tracking-[0.3em]">{{ $settings['card_eyebrow'] ?? 'Preset Ready' }}</p>
                <p class="mt-4 text-3xl font-semibold">{{ $settings['card_title'] ?? 'One product, many installs.' }}</p>
            </div>
        </div>
    </div>

    @foreach (($section['components'] ?? []) as $component)
        <div class="mx-auto max-w-6xl px-6 pb-12">{!! $component['html'] !!}</div>
    @endforeach
</section>
