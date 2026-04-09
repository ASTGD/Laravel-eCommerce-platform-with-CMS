<x-admin::layouts>
    <x-slot:title>{{ $assignment->exists ? 'Edit Assignment' : 'Create Assignment' }}</x-slot:title>

    <div class="flex items-center justify-between gap-4 pb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $assignment->exists ? 'Edit Assignment' : 'Create Assignment' }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Specific entity assignments override global defaults. Use one global assignment per page type as the fallback layout.</p>
        </div>

        @if ($assignment->exists && $assignment->scope_type === 'entity')
            <a href="{{ route('admin.cms.assignments.preview', $assignment) }}" target="_blank" class="text-sm text-blue-600 hover:underline">Preview</a>
        @endif
    </div>

    <form method="POST" action="{{ $assignment->exists ? route('admin.cms.assignments.update', $assignment) : route('admin.cms.assignments.store') }}" class="space-y-6" data-assignment-form>
        @csrf
        @if ($assignment->exists)
            @method('PUT')
        @endif

        <section class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            <div class="grid gap-6 lg:grid-cols-2">
                <label class="block">
                    <span class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Page Type</span>
                    <select name="page_type" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" data-page-type>
                        @foreach (['category_page' => 'Category Page', 'product_page' => 'Product Page'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('page_type', $assignment->page_type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('page_type') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Assigned Page</span>
                    <select name="page_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" data-page-select>
                        @foreach (['category_page' => $categoryPages, 'product_page' => $productPages] as $type => $pages)
                            @foreach ($pages as $page)
                                <option value="{{ $page->id }}" data-page-type-option="{{ $type }}" @selected((int) old('page_id', $assignment->page_id) === $page->id)>{{ $page->title }}</option>
                            @endforeach
                        @endforeach
                    </select>
                    @error('page_id') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                </label>
            </div>

            <div class="mt-6 grid gap-6 lg:grid-cols-4">
                <label class="block">
                    <span class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Scope</span>
                    <select name="scope_type" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" data-scope-type>
                        <option value="global" @selected(old('scope_type', $assignment->scope_type) === 'global')>Global Default</option>
                        <option value="entity" @selected(old('scope_type', $assignment->scope_type) === 'entity')>Exact Entity</option>
                    </select>
                    @error('scope_type') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                </label>

                <label class="block lg:col-span-2" data-entity-wrap>
                    <span class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Entity</span>
                    <select name="entity_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" data-entity-select>
                        <option value="">Select entity</option>

                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" data-entity-type-option="category_page" @selected((int) old('entity_id', $assignment->entity_id) === $category->id)>{{ $category->name }} (#{{ $category->id }})</option>
                        @endforeach

                        @foreach ($products as $product)
                            <option value="{{ $product->product_id }}" data-entity-type-option="product_page" @selected((int) old('entity_id', $assignment->entity_id) === $product->product_id)>{{ $product->name ?: $product->sku }} (#{{ $product->product_id }})</option>
                        @endforeach
                    </select>
                    @error('entity_id') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Priority</span>
                    <input type="number" min="0" name="priority" value="{{ old('priority', $assignment->priority ?? 0) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                </label>
            </div>

            <label class="mt-6 inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $assignment->is_active ?? true))>
                Active assignment
            </label>
        </section>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-gray-900">Save Assignment</button>
            <a href="{{ route('admin.cms.assignments.index') }}" class="text-sm text-gray-500 hover:underline">Back to assignments</a>
        </div>
    </form>

    <script>
        (() => {
            const form = document.querySelector('[data-assignment-form]');

            if (! form) {
                return;
            }

            const pageType = form.querySelector('[data-page-type]');
            const pageSelect = form.querySelector('[data-page-select]');
            const scopeType = form.querySelector('[data-scope-type]');
            const entityWrap = form.querySelector('[data-entity-wrap]');
            const entitySelect = form.querySelector('[data-entity-select]');

            const syncOptions = () => {
                const activeType = pageType.value;

                [...pageSelect.options].forEach((option) => {
                    option.hidden = option.dataset.pageTypeOption !== activeType;
                });

                if (pageSelect.selectedOptions[0]?.hidden) {
                    const fallback = [...pageSelect.options].find((option) => ! option.hidden);

                    if (fallback) {
                        pageSelect.value = fallback.value;
                    }
                }

                [...entitySelect.options].forEach((option) => {
                    if (! option.dataset.entityTypeOption) {
                        return;
                    }

                    option.hidden = option.dataset.entityTypeOption !== activeType;
                });

                entityWrap.style.display = scopeType.value === 'entity' ? 'block' : 'none';
            };

            pageType.addEventListener('change', syncOptions);
            scopeType.addEventListener('change', syncOptions);

            syncOptions();
        })();
    </script>
</x-admin::layouts>
