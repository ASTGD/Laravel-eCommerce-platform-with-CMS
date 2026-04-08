<x-admin::layouts>
    <x-slot:title>{{ $preset->exists ? 'Edit Theme Preset' : 'Create Theme Preset' }}</x-slot:title>

    <div class="pb-6">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
            {{ $preset->exists ? 'Edit Theme Preset' : 'Create Theme Preset' }}
        </h1>
    </div>

    <form
        method="POST"
        action="{{ $preset->exists ? route('admin.theme.presets.update', $preset) : route('admin.theme.presets.store') }}"
        class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900"
    >
        @csrf
        @if ($preset->exists)
            @method('PUT')
        @endif

        <div class="grid gap-6 md:grid-cols-2">
            <label class="block">
                <span class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Name</span>
                <input name="name" value="{{ old('name', $preset->name) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" required>
            </label>

            <label class="block">
                <span class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Code</span>
                <input name="code" value="{{ old('code', $preset->code) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono dark:border-gray-700 dark:bg-gray-950 dark:text-white">
            </label>
        </div>

        <label class="block">
            <span class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Tokens JSON</span>
            <textarea name="tokens_json" rows="14" class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono text-xs dark:border-gray-700 dark:bg-gray-950 dark:text-white">{{ old('tokens_json', json_encode($preset->tokens_json ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) }}</textarea>
        </label>

        <label class="block">
            <span class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Settings JSON</span>
            <textarea name="settings_json" rows="8" class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono text-xs dark:border-gray-700 dark:bg-gray-950 dark:text-white">{{ old('settings_json', json_encode($preset->settings_json ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) }}</textarea>
        </label>

        <div class="flex flex-wrap gap-6">
            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                <input type="checkbox" name="is_default" value="1" {{ old('is_default', $preset->is_default) ? 'checked' : '' }}>
                Default preset
            </label>

            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $preset->exists ? $preset->is_active : true) ? 'checked' : '' }}>
                Active
            </label>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-gray-900">
                Save Preset
            </button>

            <a href="{{ route('admin.theme.presets.index') }}" class="text-sm text-gray-500 hover:underline dark:text-gray-400">
                Cancel
            </a>
        </div>
    </form>
</x-admin::layouts>
