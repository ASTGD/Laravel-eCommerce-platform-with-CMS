<x-admin::layouts>
    <x-slot:title>{{ $contentEntry->exists ? 'Edit Content Entry' : 'Create Content Entry' }}</x-slot:title>

    <div class="pb-6">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $contentEntry->exists ? 'Edit Content Entry' : 'Create Content Entry' }}</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Use content entries for approved reusable messaging and FAQ content. They are not a freeform page-builder escape hatch.</p>
    </div>

    <form method="POST" action="{{ $contentEntry->exists ? route('admin.cms.content-entries.update', $contentEntry) : route('admin.cms.content-entries.store') }}" class="space-y-6">
        @csrf
        @if ($contentEntry->exists)
            @method('PUT')
        @endif

        <section class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            <div class="grid gap-6 lg:grid-cols-2">
                <label class="block">
                    <span class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Title</span>
                    <input name="title" value="{{ old('title', $contentEntry->title) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" required>
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Slug</span>
                    <input name="slug" value="{{ old('slug', $contentEntry->slug) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                </label>
            </div>

            <div class="mt-6 grid gap-6 lg:grid-cols-2">
                <label class="block">
                    <span class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Type</span>
                    <select name="type" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                        @foreach (['marketing_copy', 'faq', 'trust_badges'] as $type)
                            <option value="{{ $type }}" @selected(old('type', $contentEntry->type) === $type)>{{ str_replace('_', ' ', $type) }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Status</span>
                    <select name="status" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                        @foreach (['draft', 'published'] as $status)
                            <option value="{{ $status }}" @selected(old('status', $contentEntry->status) === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <label class="mt-6 block">
                <span class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Body JSON</span>
                <textarea name="body_json" rows="14" class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono text-xs dark:border-gray-700 dark:bg-gray-950 dark:text-white">{{ old('body_json', json_encode($contentEntry->body_json ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) }}</textarea>
            </label>
        </section>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-gray-900">Save Entry</button>
            <a href="{{ route('admin.cms.content-entries.index') }}" class="text-sm text-gray-500 hover:underline">Back to entries</a>
        </div>
    </form>
</x-admin::layouts>
