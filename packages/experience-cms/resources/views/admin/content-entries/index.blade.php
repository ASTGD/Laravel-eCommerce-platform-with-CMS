<x-admin::layouts>
    <x-slot:title>Content Entries</x-slot:title>

    <div class="flex items-center justify-between gap-4 pb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Content Entries</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Structured reusable content records for CMS-backed sections and blocks.</p>
        </div>

        <a href="{{ route('admin.cms.content-entries.create') }}" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-gray-900">
            Create Entry
        </a>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
            <thead class="bg-gray-50 dark:bg-gray-950">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Title</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Type</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Slug</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse ($contentEntries as $contentEntry)
                    <tr>
                        <td class="px-4 py-3 text-gray-900 dark:text-white">{{ $contentEntry->title }}</td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $contentEntry->type }}</td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300 font-mono">{{ $contentEntry->slug }}</td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ ucfirst($contentEntry->status) }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-3">
                                <a href="{{ route('admin.cms.content-entries.edit', $contentEntry) }}" class="text-sm text-blue-600 hover:underline">Edit</a>
                                <form method="POST" action="{{ route('admin.cms.content-entries.destroy', $contentEntry) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm text-red-600 hover:underline">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No content entries configured yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin::layouts>
