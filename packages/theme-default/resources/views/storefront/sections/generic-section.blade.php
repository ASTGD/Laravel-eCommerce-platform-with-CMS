<section class="mx-auto max-w-6xl px-6 py-12">
    @if (! empty($section['title']))
        <h2 class="text-2xl font-semibold text-slate-900">{{ $section['title'] }}</h2>
    @endif

    <pre class="mt-4 overflow-x-auto rounded-2xl bg-slate-950 p-4 text-xs text-slate-100">{{ json_encode($section['settings'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>

    @foreach (($section['components'] ?? []) as $component)
        <div class="mt-4">{!! $component['html'] !!}</div>
    @endforeach
</section>
