<x-admin::layouts>
    <x-slot:title>Theme Presets</x-slot:title>

    <div class="flex items-center justify-between gap-4 pb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Theme Presets</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Manage storefront visual presets without branching the theme architecture.</p>
        </div>

        <a
            href="{{ route('admin.theme.presets.create') }}"
            class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-gray-900"
        >
            New Preset
        </a>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
            <thead class="bg-gray-50 dark:bg-gray-950">
                <tr class="text-left text-gray-600 dark:text-gray-300">
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Code</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Default</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse ($presets as $preset)
                    <tr class="text-gray-700 dark:text-gray-200">
                        <td class="px-4 py-3">{{ $preset->name }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $preset->code }}</td>
                        <td class="px-4 py-3">{{ $preset->is_active ? 'Active' : 'Inactive' }}</td>
                        <td class="px-4 py-3">{{ $preset->is_default ? 'Yes' : 'No' }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-3">
                                <a href="{{ route('admin.theme.presets.edit', $preset) }}" class="text-blue-600 hover:underline">Edit</a>

                                <form method="POST" action="{{ route('admin.theme.presets.destroy', $preset) }}">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                            No theme presets created yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin::layouts>
