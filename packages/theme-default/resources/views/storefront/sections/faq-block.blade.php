@php
    $entry = collect($section['items'] ?? [])->first();
    $entryItems = $entry?->body_json['items'] ?? [];
    $items = $entryItems !== [] ? $entryItems : ($section['settings']['items'] ?? []);
@endphp
<section class="rounded-[2rem] bg-white p-8 shadow-sm ring-1 ring-slate-200">
    <h2 class="text-2xl font-semibold text-slate-900">{{ $section['settings']['headline'] ?? 'FAQ' }}</h2>

    <div class="mt-6 space-y-4">
        @foreach ($items as $item)
            <div class="rounded-xl border border-slate-200 p-4">
                <p class="font-medium text-slate-900">{{ $item['question'] ?? 'Question' }}</p>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $item['answer'] ?? '' }}</p>
            </div>
        @endforeach
    </div>
</section>
