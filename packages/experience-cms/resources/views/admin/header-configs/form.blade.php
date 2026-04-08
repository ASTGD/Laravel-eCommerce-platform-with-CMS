<x-admin::layouts>
    <x-slot:title>{{ $headerConfig->exists ? 'Edit Header Config' : 'Create Header Config' }}</x-slot:title>

    <div class="pb-6"><h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $headerConfig->exists ? 'Edit Header Config' : 'Create Header Config' }}</h1></div>

    <form method="POST" action="{{ $headerConfig->exists ? route('admin.cms.header-configs.update', $headerConfig) : route('admin.cms.header-configs.store') }}" class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
        @csrf
        @if ($headerConfig->exists)
            @method('PUT')
        @endif

        <label class="block"><span class="mb-2 block text-sm font-medium">Code</span><input name="code" value="{{ old('code', $headerConfig->code) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono dark:border-gray-700 dark:bg-gray-950 dark:text-white"></label>
        <label class="block"><span class="mb-2 block text-sm font-medium">Settings JSON</span><textarea name="settings_json" rows="14" class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono text-xs dark:border-gray-700 dark:bg-gray-950 dark:text-white">{{ old('settings_json', json_encode($headerConfig->settings_json ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) }}</textarea></label>
        <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_default" value="1" {{ old('is_default', $headerConfig->is_default) ? 'checked' : '' }}> Default header</label>

        <div><button type="submit" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-gray-900">Save Header</button></div>
    </form>
</x-admin::layouts>
