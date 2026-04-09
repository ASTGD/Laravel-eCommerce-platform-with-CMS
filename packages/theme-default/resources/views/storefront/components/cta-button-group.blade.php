<div class="flex flex-wrap gap-3">
    @foreach (($component['settings']['buttons'] ?? []) as $button)
        <a href="{{ $button['url'] ?? '#' }}" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-medium text-slate-800">
            {{ $button['label'] ?? 'Action' }}
        </a>
    @endforeach
</div>
