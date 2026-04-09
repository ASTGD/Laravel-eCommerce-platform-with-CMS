@php
    $badges = $section['settings']['badges'] ?? [];
    if ($badges === []) {
        $badges = $siteSettings['store.trust_badges']['badges'] ?? [];
    }
@endphp
<section class="rounded-[2rem] bg-white p-6 shadow-sm ring-1 ring-slate-200">
    <h2 class="text-lg font-semibold text-slate-900">{{ $section['settings']['headline'] ?? 'Why shop with us' }}</h2>

    <div class="mt-4 flex flex-wrap gap-2">
        @foreach ($badges as $badge)
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">{{ $badge['label'] ?? 'Badge' }}</span>
        @endforeach
    </div>
</section>
