@php($settings = $section['settings'])
<section class="mx-auto max-w-4xl px-6 py-14">
    <div class="rounded-[2rem] bg-white p-8 shadow-sm ring-1 ring-slate-200">
        @if (! empty($section['title']))
            <h2 class="text-3xl font-semibold text-slate-900">{{ $section['title'] }}</h2>
        @endif

        <div class="prose mt-4 max-w-none text-slate-600">
            {!! nl2br(e($settings['content'] ?? 'Structured content block.')) !!}
        </div>
    </div>
</section>
