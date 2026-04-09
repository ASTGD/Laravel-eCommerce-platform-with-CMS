<div class="flex flex-wrap gap-2">
    @foreach (($component['settings']['badges'] ?? []) as $badge)
        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">{{ $badge['label'] ?? 'Badge' }}</span>
    @endforeach
</div>
