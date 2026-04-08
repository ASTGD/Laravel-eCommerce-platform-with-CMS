<x-admin::layouts>
    <x-slot:title>{{ $sectionType->exists ? 'Edit Section Type' : 'Create Section Type' }}</x-slot:title>

    <div class="pb-6">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $sectionType->exists ? 'Edit Section Type' : 'Create Section Type' }}</h1>
    </div>

    <form method="POST" action="{{ $sectionType->exists ? route('admin.cms.section-types.update', $sectionType) : route('admin.cms.section-types.store') }}" class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
        @csrf
        @if ($sectionType->exists)
            @method('PUT')
        @endif

        <div class="grid gap-6 md:grid-cols-3">
            <label class="block"><span class="mb-2 block text-sm font-medium">Name</span><input name="name" value="{{ old('name', $sectionType->name) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" required></label>
            <label class="block"><span class="mb-2 block text-sm font-medium">Code</span><input name="code" value="{{ old('code', $sectionType->code) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono dark:border-gray-700 dark:bg-gray-950 dark:text-white"></label>
            <label class="block"><span class="mb-2 block text-sm font-medium">Category</span><input name="category" value="{{ old('category', $sectionType->category) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" required></label>
        </div>

        <label class="block"><span class="mb-2 block text-sm font-medium">Config Schema JSON</span><textarea name="config_schema_json" rows="10" class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono text-xs dark:border-gray-700 dark:bg-gray-950 dark:text-white">{{ old('config_schema_json', json_encode($sectionType->config_schema_json ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) }}</textarea></label>

        <label class="block"><span class="mb-2 block text-sm font-medium">Allowed Data Sources JSON</span><textarea name="allowed_data_sources_json" rows="6" class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono text-xs dark:border-gray-700 dark:bg-gray-950 dark:text-white">{{ old('allowed_data_sources_json', json_encode($sectionType->allowed_data_sources_json ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) }}</textarea></label>

        <label class="block"><span class="mb-2 block text-sm font-medium">Renderer Class</span><input name="renderer_class" value="{{ old('renderer_class', $sectionType->renderer_class) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono text-xs dark:border-gray-700 dark:bg-gray-950 dark:text-white"></label>

        <div class="flex gap-6">
            <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="supports_components" value="1" {{ old('supports_components', $sectionType->supports_components) ? 'checked' : '' }}> Supports Components</label>
            <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" {{ old('is_active', $sectionType->exists ? $sectionType->is_active : true) ? 'checked' : '' }}> Active</label>
        </div>

        <div><button type="submit" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-gray-900">Save Section Type</button></div>
    </form>
</x-admin::layouts>
