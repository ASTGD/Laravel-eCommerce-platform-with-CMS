<x-admin::layouts>
    <x-slot:title>Site Settings</x-slot:title>

    <div class="flex items-center justify-between gap-4 pb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Site Settings</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Structured global settings resolved into storefront payloads, headers, footers, category pages, and PDP blocks.</p>
        </div>

        <a href="{{ route('admin.cms.site-settings.create') }}" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-gray-900">
            Add Setting
        </a>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
            <thead class="bg-gray-50 dark:bg-gray-950">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Key</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Group</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Preview</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse ($siteSettings as $siteSetting)
                    <tr>
                        <td class="px-4 py-3 font-mono text-gray-900 dark:text-white">{{ $siteSetting->key }}</td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $siteSetting->group }}</td>
                        <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-300">
                            <pre class="max-w-xl overflow-x-auto whitespace-pre-wrap">{{ json_encode($siteSetting->value_json ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.cms.site-settings.edit', $siteSetting) }}" class="text-sm text-blue-600 hover:underline">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No site settings configured yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin::layouts>
