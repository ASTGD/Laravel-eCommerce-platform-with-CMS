<x-admin::layouts>
    <x-slot:title>{{ $template->exists ? 'Edit Template' : 'Create Template' }}</x-slot:title>

    <div class="pb-6">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $template->exists ? 'Edit Template' : 'Create Template' }}</h1>
    </div>

    <form method="POST" action="{{ $template->exists ? route('admin.cms.templates.update', $template) : route('admin.cms.templates.store') }}" class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
        @csrf
        @if ($template->exists)
            @method('PUT')
        @endif

        <div class="grid gap-6 md:grid-cols-3">
            <label class="block">
                <span class="mb-2 block text-sm font-medium">Name</span>
                <input name="name" value="{{ old('name', $template->name) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" required>
            </label>
            <label class="block">
                <span class="mb-2 block text-sm font-medium">Code</span>
                <input name="code" value="{{ old('code', $template->code) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono dark:border-gray-700 dark:bg-gray-950 dark:text-white">
            </label>
            <label class="block">
                <span class="mb-2 block text-sm font-medium">Page Type</span>
                <input name="page_type" value="{{ old('page_type', $template->page_type) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" required>
            </label>
        </div>

        <label class="block">
            <span class="mb-2 block text-sm font-medium">Schema JSON</span>
            <textarea name="schema_json" rows="12" class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono text-xs dark:border-gray-700 dark:bg-gray-950 dark:text-white">{{ old('schema_json', json_encode($template->schema_json ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) }}</textarea>
        </label>

        <label class="inline-flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $template->exists ? $template->is_active : true) ? 'checked' : '' }}>
            Active
        </label>

        <div>
            <button type="submit" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-gray-900">Save Template</button>
        </div>
    </form>
</x-admin::layouts>
