<x-admin::layouts>
    <x-slot:title>Section Types</x-slot:title>

    <div class="flex items-center justify-between gap-4 pb-6">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Section Types</h1>
        <a href="{{ route('admin.cms.section-types.create') }}" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-gray-900">New Section Type</a>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
            <thead class="bg-gray-50 dark:bg-gray-950">
                <tr class="text-left text-gray-600 dark:text-gray-300">
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Code</th>
                    <th class="px-4 py-3">Category</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                @foreach ($sectionTypes as $sectionType)
                    <tr class="text-gray-700 dark:text-gray-200">
                        <td class="px-4 py-3">{{ $sectionType->name }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $sectionType->code }}</td>
                        <td class="px-4 py-3">{{ $sectionType->category }}</td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('admin.cms.section-types.edit', $sectionType) }}" class="text-blue-600 hover:underline">Edit</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-admin::layouts>
