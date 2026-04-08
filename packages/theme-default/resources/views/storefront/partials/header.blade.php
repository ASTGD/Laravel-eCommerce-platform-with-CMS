@php($settings = $header?->settings_json ?? [])
<header class="border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
        <div>
            <p class="text-xs uppercase tracking-[0.3em] text-slate-500">{{ $settings['announcement'] ?? 'Reusable Commerce Platform' }}</p>
            <a href="{{ url('/') }}" class="text-2xl font-semibold text-slate-900">
                {{ $settings['brand_name'] ?? config('app.name') }}
            </a>
        </div>

        <nav class="hidden gap-6 text-sm text-slate-600 md:flex">
            @foreach (($settings['links'] ?? [['label' => 'Catalog', 'url' => '/'], ['label' => 'Contact', 'url' => '/contact-us']]) as $link)
                <a href="{{ $link['url'] ?? '#' }}" class="hover:text-slate-900">{{ $link['label'] ?? 'Link' }}</a>
            @endforeach
        </nav>
    </div>
</header>
