<x-admin::layouts>
    <x-slot:title>Templates</x-slot:title>

    <div class="flex items-center justify-between gap-4 pb-6">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Templates</h1>
        <a href="{{ route('admin.cms.templates.create') }}" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-gray-900">New Template</a>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
            <thead class="bg-gray-50 dark:bg-gray-950">
                <tr class="text-left text-gray-600 dark:text-gray-300">
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Code</th>
                    <th class="px-4 py-3">Page Type</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                @foreach ($templates as $template)
                    <tr class="text-gray-700 dark:text-gray-200">
                        <td class="px-4 py-3">{{ $template->name }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $template->code }}</td>
                        <td class="px-4 py-3">{{ $template->page_type }}</td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('admin.cms.templates.edit', $template) }}" class="text-blue-600 hover:underline">Edit</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-admin::layouts>
