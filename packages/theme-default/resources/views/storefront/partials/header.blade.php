@php
    $settings = array_replace($siteSettings['store.identity'] ?? [], $header?->settings_json ?? []);
    $menuItems = ($menu?->items ?? collect())->whereNull('parent_id')->sortBy('sort_order')->values();
    $textValue = static function (mixed $value, string $fallback): string {
        if (is_array($value)) {
            return (string) ($value['text'] ?? $value['label'] ?? $value['name'] ?? $fallback);
        }

        return (string) ($value ?: $fallback);
    };
    $announcement = $textValue($settings['announcement'] ?? null, 'Reusable Commerce Platform');
    $brandName = $textValue($settings['brand_name'] ?? $settings['name'] ?? null, config('app.name'));
@endphp
<header class="border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
        <div>
            <p class="text-xs uppercase tracking-[0.3em] text-slate-500">{{ $announcement }}</p>
            <a href="{{ url('/') }}" class="text-2xl font-semibold text-slate-900">
                {{ $brandName }}
            </a>
        </div>

        <nav class="hidden gap-6 text-sm text-slate-600 md:flex">
            @if ($menuItems->isNotEmpty())
                @foreach ($menuItems as $item)
                    <a href="{{ $item->target ?: '#' }}" class="hover:text-slate-900">{{ $item->title }}</a>
                @endforeach
            @else
                @foreach (($settings['links'] ?? [['label' => 'Catalog', 'url' => '/'], ['label' => 'Contact', 'url' => '/contact-us']]) as $link)
                    <a href="{{ is_string($link['url'] ?? null) ? $link['url'] : '#' }}" class="hover:text-slate-900">
                        {{ $textValue($link['label'] ?? null, 'Link') }}
                    </a>
                @endforeach
            @endif
        </nav>
    </div>
</header>
