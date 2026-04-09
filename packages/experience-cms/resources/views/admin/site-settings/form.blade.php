<x-admin::layouts>
    <x-slot:title>{{ $siteSetting->exists ? 'Edit Site Setting' : 'Create Site Setting' }}</x-slot:title>

    <div class="pb-6">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $siteSetting->exists ? 'Edit Site Setting' : 'Create Site Setting' }}</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Use only the supported structured keys. Global site settings are not a replacement for page composition.</p>
    </div>

    <form method="POST" action="{{ $siteSetting->exists ? route('admin.cms.site-settings.update', $siteSetting) : route('admin.cms.site-settings.store') }}" class="space-y-6">
        @csrf
        @if ($siteSetting->exists)
            @method('PUT')
        @endif

        <section class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            <div class="grid gap-6 lg:grid-cols-2">
                <label class="block">
                    <span class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Key</span>
                    <select name="key" class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                        @foreach ([
                            'store.identity',
                            'store.contact',
                            'store.social_links',
                            'store.trust_badges',
                            'store.category_page',
                            'store.product_page',
                        ] as $key)
                            <option value="{{ $key }}" @selected(old('key', $siteSetting->key) === $key)>{{ $key }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Group</span>
                    <input name="group" value="{{ old('group', $siteSetting->group) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                </label>
            </div>

            <label class="mt-6 block">
                <span class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Value JSON</span>
                <textarea name="value_json" rows="16" class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono text-xs dark:border-gray-700 dark:bg-gray-950 dark:text-white">{{ old('value_json', json_encode($siteSetting->value_json ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) }}</textarea>
            </label>
        </section>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-gray-900">Save Setting</button>
            <a href="{{ route('admin.cms.site-settings.index') }}" class="text-sm text-gray-500 hover:underline">Back to settings</a>
        </div>
    </form>
</x-admin::layouts>
