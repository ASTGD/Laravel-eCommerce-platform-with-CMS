<x-admin::layouts>
    <x-slot:title>{{ $footerConfig->exists ? 'Edit Footer Config' : 'Create Footer Config' }}</x-slot:title>

    <div class="pb-6"><h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $footerConfig->exists ? 'Edit Footer Config' : 'Create Footer Config' }}</h1></div>

    <form method="POST" action="{{ $footerConfig->exists ? route('admin.cms.footer-configs.update', $footerConfig) : route('admin.cms.footer-configs.store') }}" class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
        @csrf
        @if ($footerConfig->exists)
            @method('PUT')
        @endif

        <label class="block"><span class="mb-2 block text-sm font-medium">Code</span><input name="code" value="{{ old('code', $footerConfig->code) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono dark:border-gray-700 dark:bg-gray-950 dark:text-white"></label>
        <label class="block"><span class="mb-2 block text-sm font-medium">Settings JSON</span><textarea name="settings_json" rows="14" class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono text-xs dark:border-gray-700 dark:bg-gray-950 dark:text-white">{{ old('settings_json', json_encode($footerConfig->settings_json ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) }}</textarea></label>
        <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_default" value="1" {{ old('is_default', $footerConfig->is_default) ? 'checked' : '' }}> Default footer</label>

        <div><button type="submit" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-gray-900">Save Footer</button></div>
    </form>
</x-admin::layouts>
