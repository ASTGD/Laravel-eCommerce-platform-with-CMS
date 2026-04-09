@php($settings = $header?->settings_json ?? [])
@php($menuItems = ($menu?->items ?? collect())->whereNull('parent_id')->sortBy('sort_order')->values())
<header class="border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
        <div>
            <p class="text-xs uppercase tracking-[0.3em] text-slate-500">{{ $settings['announcement'] ?? 'Reusable Commerce Platform' }}</p>
            <a href="{{ url('/') }}" class="text-2xl font-semibold text-slate-900">
                {{ $settings['brand_name'] ?? config('app.name') }}
            </a>
        </div>

        <nav class="hidden gap-6 text-sm text-slate-600 md:flex">
            @if ($menuItems->isNotEmpty())
                @foreach ($menuItems as $item)
                    <a href="{{ $item->target ?: '#' }}" class="hover:text-slate-900">{{ $item->title }}</a>
                @endforeach
            @else
                @foreach (($settings['links'] ?? [['label' => 'Catalog', 'url' => '/'], ['label' => 'Contact', 'url' => '/contact-us']]) as $link)
                    <a href="{{ $link['url'] ?? '#' }}" class="hover:text-slate-900">{{ $link['label'] ?? 'Link' }}</a>
                @endforeach
            @endif
        </nav>
    </div>
</header>
