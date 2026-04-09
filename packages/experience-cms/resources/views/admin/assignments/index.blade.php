<x-admin::layouts>
    <x-slot:title>Page Assignments</x-slot:title>

    <div class="flex items-center justify-between gap-4 pb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Page Assignments</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Exact entity assignments take precedence over global defaults for category and product pages.</p>
        </div>

        <a href="{{ route('admin.cms.assignments.create') }}" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-gray-900">
            Create Assignment
        </a>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
            <thead class="bg-gray-50 dark:bg-gray-950">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Type</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Scope</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Page</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Entity</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Priority</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse ($assignments as $assignment)
                    <tr>
                        <td class="px-4 py-3 text-gray-900 dark:text-white">{{ str_replace('_', ' ', $assignment->page_type) }}</td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $assignment->scope_type }}</td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $assignment->page?->title }}</td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                            {{ $assignment->entity_type ? sprintf('%s #%s', $assignment->entity_type, $assignment->entity_id ?: 'default') : 'default' }}
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $assignment->priority }}</td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $assignment->is_active ? 'Active' : 'Inactive' }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-3">
                                @if ($assignment->scope_type === 'entity')
                                    <a href="{{ route('admin.cms.assignments.preview', $assignment) }}" class="text-sm text-blue-600 hover:underline" target="_blank">Preview</a>
                                @endif

                                <a href="{{ route('admin.cms.assignments.edit', $assignment) }}" class="text-sm text-blue-600 hover:underline">Edit</a>

                                <form method="POST" action="{{ route('admin.cms.assignments.destroy', $assignment) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm text-red-600 hover:underline">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">No assignments configured yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin::layouts>
