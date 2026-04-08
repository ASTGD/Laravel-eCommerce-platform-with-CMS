<x-admin::layouts>
    <x-slot:title>{{ $page->exists ? 'Edit Page' : 'Create Page' }}</x-slot:title>

    <div class="pb-6">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $page->exists ? 'Edit Page' : 'Create Page' }}</h1>
    </div>

    <form method="POST" action="{{ $page->exists ? route('admin.cms.pages.update', $page) : route('admin.cms.pages.store') }}" class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
        @csrf
        @if ($page->exists)
            @method('PUT')
        @endif

        <div class="grid gap-6 md:grid-cols-2">
            <label class="block">
                <span class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Title</span>
                <input name="title" value="{{ old('title', $page->title) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" required>
            </label>

            <label class="block">
                <span class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Slug</span>
                <input name="slug" value="{{ old('slug', $page->slug) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono dark:border-gray-700 dark:bg-gray-950 dark:text-white">
            </label>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <label class="block">
                <span class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Type</span>
                <select name="type" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                    @foreach (['homepage', 'content_page', 'campaign_page', 'category_page', 'product_page'] as $type)
                        <option value="{{ $type }}" @selected(old('type', $page->type) === $type)>{{ $type }}</option>
                    @endforeach
                </select>
            </label>

            <label class="block">
                <span class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Template</span>
                <select name="template_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                    <option value="">No template</option>
                    @foreach ($templates as $template)
                        <option value="{{ $template->id }}" @selected((string) old('template_id', $page->template_id) === (string) $template->id)>{{ $template->name }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        @if ($page->exists)
            <div class="rounded-lg bg-slate-50 p-4 text-sm text-slate-600 dark:bg-gray-950 dark:text-slate-300">
                Status: <strong>{{ ucfirst($page->status) }}</strong>
                @if ($page->published_at)
                    <span class="ml-2">Published at {{ $page->published_at->toDateTimeString() }}</span>
                @endif
            </div>
        @endif

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-gray-900">Save Page</button>

            @if ($page->exists)
                <a href="{{ route('admin.cms.pages.preview', $page) }}" class="text-sm text-blue-600 hover:underline">Preview</a>
            @endif
        </div>
    </form>
</x-admin::layouts>
