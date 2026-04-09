@php
    $itemRows = old('items', $menu->exists
        ? $menu->items->map(fn ($item) => [
            'id' => $item->id,
            'title' => $item->title,
            'type' => $item->type,
            'target' => $item->target,
            'sort_order' => $item->sort_order,
            'is_active' => $item->is_active,
        ])->all()
        : []);

    if ($itemRows === []) {
        $itemRows = [[
            'id' => null,
            'title' => null,
            'type' => 'url',
            'target' => null,
            'sort_order' => 1,
            'is_active' => true,
        ]];
    }

    $itemRows = array_values($itemRows);
@endphp

<x-admin::layouts>
    <x-slot:title>{{ $menu->exists ? 'Edit Menu' : 'Create Menu' }}</x-slot:title>

    <div class="pb-6">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $menu->exists ? 'Edit Menu' : 'Create Menu' }}</h1>
    </div>

    <form method="POST" action="{{ $menu->exists ? route('admin.cms.menus.update', $menu) : route('admin.cms.menus.store') }}" class="space-y-6" data-menu-editor>
        @csrf
        @if ($menu->exists)
            @method('PUT')
        @endif

        <section class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            <div class="grid gap-6 md:grid-cols-3">
                <label class="block">
                    <span class="mb-2 block text-sm font-medium">Name</span>
                    <input name="name" value="{{ old('name', $menu->name) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" required>
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-medium">Code</span>
                    <input name="code" value="{{ old('code', $menu->code) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-medium">Location</span>
                    <input name="location" value="{{ old('location', $menu->location) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" required>
                </label>
            </div>

            <label class="mt-6 inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $menu->exists ? $menu->is_active : true) ? 'checked' : '' }}>
                Active
            </label>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Menu Items</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">This slice supports flat navigation items. Nested menu editing remains a follow-up task.</p>
                </div>

                <button type="button" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-950" data-add-item>
                    Add Item
                </button>
            </div>

            <div class="mt-6 space-y-4" data-item-list>
                @foreach ($itemRows as $index => $itemRow)
                    <div class="rounded-xl border border-slate-200 p-4 dark:border-gray-700" data-item-row>
                        <input type="hidden" name="items[{{ $index }}][id]" value="{{ $itemRow['id'] ?? '' }}">

                        <div class="grid gap-4 lg:grid-cols-[1.2fr_180px_1.2fr_120px_auto]">
                            <label class="block">
                                <span class="mb-2 block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Title</span>
                                <input name="items[{{ $index }}][title]" value="{{ $itemRow['title'] ?? '' }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                            </label>

                            <label class="block">
                                <span class="mb-2 block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Type</span>
                                <select name="items[{{ $index }}][type]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                                    @foreach (['url', 'page', 'category'] as $type)
                                        <option value="{{ $type }}" @selected(($itemRow['type'] ?? 'url') === $type)>{{ strtoupper($type) }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="block">
                                <span class="mb-2 block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Target</span>
                                <input name="items[{{ $index }}][target]" value="{{ $itemRow['target'] ?? '' }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                            </label>

                            <label class="block">
                                <span class="mb-2 block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Sort</span>
                                <input type="number" min="0" name="items[{{ $index }}][sort_order]" value="{{ $itemRow['sort_order'] ?? 0 }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                            </label>

                            <div class="flex items-end justify-between gap-4">
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                    <input type="hidden" name="items[{{ $index }}][is_active]" value="0">
                                    <input type="checkbox" name="items[{{ $index }}][is_active]" value="1" @checked(($itemRow['is_active'] ?? false))>
                                    Active
                                </label>

                                <button type="button" class="text-sm text-red-600 hover:underline" data-remove-item>Remove</button>
                            </div>
                        </div>

                        @foreach ($errors->get("items.$index.*") as $messages)
                            @foreach ($messages as $message)
                                <p class="mt-3 text-xs text-red-600">{{ $message }}</p>
                            @endforeach
                        @endforeach
                    </div>
                @endforeach
            </div>

            <template data-item-template>
                <div class="rounded-xl border border-slate-200 p-4 dark:border-gray-700" data-item-row>
                    <input type="hidden" name="items[__INDEX__][id]" value="">

                    <div class="grid gap-4 lg:grid-cols-[1.2fr_180px_1.2fr_120px_auto]">
                        <label class="block">
                            <span class="mb-2 block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Title</span>
                            <input name="items[__INDEX__][title]" value="" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Type</span>
                            <select name="items[__INDEX__][type]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                                <option value="url">URL</option>
                                <option value="page">PAGE</option>
                                <option value="category">CATEGORY</option>
                            </select>
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Target</span>
                            <input name="items[__INDEX__][target]" value="" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Sort</span>
                            <input type="number" min="0" name="items[__INDEX__][sort_order]" value="0" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                        </label>

                        <div class="flex items-end justify-between gap-4">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                <input type="hidden" name="items[__INDEX__][is_active]" value="0">
                                <input type="checkbox" name="items[__INDEX__][is_active]" value="1" checked>
                                Active
                            </label>

                            <button type="button" class="text-sm text-red-600 hover:underline" data-remove-item>Remove</button>
                        </div>
                    </div>
                </div>
            </template>
        </section>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-gray-900">Save Menu</button>
            <a href="{{ route('admin.cms.menus.index') }}" class="text-sm text-gray-500 hover:underline dark:text-gray-400">Cancel</a>
        </div>
    </form>

    <script>
        (() => {
            const editor = document.querySelector('[data-menu-editor]');

            if (! editor) {
                return;
            }

            const list = editor.querySelector('[data-item-list]');
            const template = editor.querySelector('[data-item-template]');
            const addButton = editor.querySelector('[data-add-item]');
            let nextIndex = list.querySelectorAll('[data-item-row]').length;

            const bindRow = (row) => {
                row.querySelector('[data-remove-item]').addEventListener('click', () => row.remove());
            };

            addButton.addEventListener('click', () => {
                const index = nextIndex++;
                const html = template.innerHTML.replaceAll('__INDEX__', index);
                const wrapper = document.createElement('div');

                wrapper.innerHTML = html.trim();

                const row = wrapper.firstElementChild;

                list.appendChild(row);
                bindRow(row);
            });

            list.querySelectorAll('[data-item-row]').forEach(bindRow);
        })();
    </script>
</x-admin::layouts>
