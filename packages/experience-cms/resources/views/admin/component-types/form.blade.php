<x-admin::layouts>
    <x-slot:title>{{ $componentType->exists ? 'Edit Component Type' : 'Create Component Type' }}</x-slot:title>

    <div class="pb-6">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $componentType->exists ? 'Edit Component Type' : 'Create Component Type' }}</h1>
    </div>

    <form method="POST" action="{{ $componentType->exists ? route('admin.cms.component-types.update', $componentType) : route('admin.cms.component-types.store') }}" class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
        @csrf
        @if ($componentType->exists)
            @method('PUT')
        @endif

        <div class="grid gap-6 md:grid-cols-2">
            <label class="block">
                <span class="mb-2 block text-sm font-medium">Name</span>
                <input name="name" value="{{ old('name', $componentType->name) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" required>
            </label>

            <label class="block">
                <span class="mb-2 block text-sm font-medium">Code</span>
                <input name="code" value="{{ old('code', $componentType->code) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono dark:border-gray-700 dark:bg-gray-950 dark:text-white">
            </label>
        </div>

        <label class="block">
            <span class="mb-2 block text-sm font-medium">Config Schema JSON</span>
            <textarea name="config_schema_json" rows="10" class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono text-xs dark:border-gray-700 dark:bg-gray-950 dark:text-white">{{ old('config_schema_json', json_encode($componentType->config_schema_json ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) }}</textarea>
        </label>

        <label class="block">
            <span class="mb-2 block text-sm font-medium">Renderer Class</span>
            <input name="renderer_class" value="{{ old('renderer_class', $componentType->renderer_class) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono text-xs dark:border-gray-700 dark:bg-gray-950 dark:text-white">
        </label>

        <label class="inline-flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $componentType->exists ? $componentType->is_active : true) ? 'checked' : '' }}>
            Active
        </label>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-gray-900">Save Component Type</button>
            <a href="{{ route('admin.cms.component-types.index') }}" class="text-sm text-gray-500 hover:underline dark:text-gray-400">Cancel</a>
        </div>
    </form>
</x-admin::layouts>
