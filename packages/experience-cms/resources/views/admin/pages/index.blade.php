<x-admin::layouts>
    <x-slot:title>Pages</x-slot:title>

    <div class="flex items-center justify-between gap-4 pb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Pages</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Manage structured pages and publish them through the CMS workflow.</p>
        </div>

        <a href="{{ route('admin.cms.pages.create') }}" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-gray-900">
            New Page
        </a>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
            <thead class="bg-gray-50 dark:bg-gray-950">
                <tr class="text-left text-gray-600 dark:text-gray-300">
                    <th class="px-4 py-3">Title</th>
                    <th class="px-4 py-3">Slug</th>
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse ($pages as $page)
                    <tr class="text-gray-700 dark:text-gray-200">
                        <td class="px-4 py-3">{{ $page->title }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $page->slug }}</td>
                        <td class="px-4 py-3">{{ $page->type }}</td>
                        <td class="px-4 py-3">{{ ucfirst($page->status) }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-3">
                                <a href="{{ route('admin.cms.pages.preview', $page) }}" class="text-slate-600 hover:underline">Preview</a>
                                <a href="{{ route('admin.cms.pages.edit', $page) }}" class="text-blue-600 hover:underline">Edit</a>
                                <form method="POST" action="{{ route('admin.cms.pages.publish', $page) }}">
                                    @csrf
                                    <button type="submit" class="text-emerald-600 hover:underline">Publish</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">No pages created yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin::layouts>
