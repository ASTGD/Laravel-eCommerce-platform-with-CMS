@php
    $entry = collect($section['items'] ?? [])->first();
    $entryBody = $entry?->body_json ?? [];
@endphp
<section class="rounded-[2rem] bg-white p-8 shadow-sm ring-1 ring-slate-200">
    <p class="text-xs uppercase tracking-[0.3em] text-slate-400">{{ $section['settings']['headline'] ?? ($entryBody['headline'] ?? 'Category Intro') }}</p>
    <div class="prose mt-4 max-w-none text-slate-600">
        {!! nl2br(e($entryBody['content'] ?? $section['settings']['content'] ?? '')) !!}
    </div>

    @foreach (($section['components'] ?? []) as $component)
        <div class="mt-4">{!! $component['html'] !!}</div>
    @endforeach
</section>
