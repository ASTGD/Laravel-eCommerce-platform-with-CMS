<div class="grid gap-2">
    @foreach (($component['settings']['links'] ?? []) as $link)
        <a href="{{ $link['url'] ?? '#' }}" class="text-sm text-slate-600 hover:text-slate-900">{{ $link['label'] ?? 'Link' }}</a>
    @endforeach
</div>
