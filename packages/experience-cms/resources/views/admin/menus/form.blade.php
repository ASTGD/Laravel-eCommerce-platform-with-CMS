<x-admin::layouts>
    <x-slot:title>{{ $menu->exists ? 'Edit Menu' : 'Create Menu' }}</x-slot:title>

    <div class="pb-6">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $menu->exists ? 'Edit Menu' : 'Create Menu' }}</h1>
    </div>

    <form method="POST" action="{{ $menu->exists ? route('admin.cms.menus.update', $menu) : route('admin.cms.menus.store') }}" class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
        @csrf
        @if ($menu->exists)
            @method('PUT')
        @endif

        <div class="grid gap-6 md:grid-cols-3">
            <label class="block"><span class="mb-2 block text-sm font-medium">Name</span><input name="name" value="{{ old('name', $menu->name) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" required></label>
            <label class="block"><span class="mb-2 block text-sm font-medium">Code</span><input name="code" value="{{ old('code', $menu->code) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono dark:border-gray-700 dark:bg-gray-950 dark:text-white"></label>
            <label class="block"><span class="mb-2 block text-sm font-medium">Location</span><input name="location" value="{{ old('location', $menu->location) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" required></label>
        </div>

        <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" {{ old('is_active', $menu->exists ? $menu->is_active : true) ? 'checked' : '' }}> Active</label>

        <div><button type="submit" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-gray-900">Save Menu</button></div>
    </form>
</x-admin::layouts>
