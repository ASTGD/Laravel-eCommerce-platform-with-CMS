@php
    $sectionRows = old('sections', $page->exists
        ? $page->sections->map(fn ($section) => [
            'id' => $section->id,
            'template_area_id' => $section->template_area_id,
            'section_type_id' => $section->section_type_id,
            'title' => $section->title,
            'sort_order' => $section->sort_order,
            'is_active' => $section->is_active,
            'settings_json' => json_encode($section->settings_json ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'data_source_type' => $section->data_source_type,
            'data_source_payload_json' => json_encode($section->data_source_payload_json ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        ])->all()
        : []);

    if ($sectionRows === []) {
        $sectionRows = [[
            'id' => null,
            'template_area_id' => null,
            'section_type_id' => null,
            'title' => null,
            'sort_order' => 1,
            'is_active' => true,
            'settings_json' => '{}',
            'data_source_type' => null,
            'data_source_payload_json' => '{}',
        ]];
    }

    $sectionRows = array_values($sectionRows);
@endphp

<x-admin::layouts>
    <x-slot:title>{{ $page->exists ? 'Edit Page' : 'Create Page' }}</x-slot:title>

    <div class="flex items-start justify-between gap-4 pb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $page->exists ? 'Edit Page' : 'Create Page' }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage the page definition, structured sections, preview, and publish state from one screen.</p>
        </div>

        @if ($page->exists)
            <div class="text-right text-sm text-gray-500 dark:text-gray-400">
                <p>Status: <span class="font-medium text-gray-900 dark:text-white">{{ ucfirst($page->status) }}</span></p>
                @if ($page->published_at)
                    <p class="mt-1">Published {{ $page->published_at->toDateTimeString() }}</p>
                @endif
            </div>
        @endif
    </div>

    <form method="POST" action="{{ $page->exists ? route('admin.cms.pages.update', $page) : route('admin.cms.pages.store') }}" class="space-y-6" data-page-editor data-template-areas='@json($templateAreasByTemplate)'>
        @csrf
        @if ($page->exists)
            @method('PUT')
        @endif

        <section class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            <div class="grid gap-6 lg:grid-cols-2">
                <label class="block">
                    <span class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Title</span>
                    <input name="title" value="{{ old('title', $page->title) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" required>
                    @error('title') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Slug</span>
                    <input name="slug" value="{{ old('slug', $page->slug) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                    @error('slug') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                </label>
            </div>

            <div class="mt-6 grid gap-6 lg:grid-cols-4">
                <label class="block">
                    <span class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Type</span>
                    <select name="type" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                        @foreach (['homepage', 'content_page', 'campaign_page', 'category_page', 'product_page'] as $type)
                            <option value="{{ $type }}" @selected(old('type', $page->type) === $type)>{{ str_replace('_', ' ', $type) }}</option>
                        @endforeach
                    </select>
                    @error('type') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Template</span>
                    <select name="template_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white" data-template-select>
                        <option value="">Select template</option>
                        @foreach ($templates as $template)
                            <option value="{{ $template->id }}" @selected((string) old('template_id', $page->template_id) === (string) $template->id)>{{ $template->name }}</option>
                        @endforeach
                    </select>
                    @error('template_id') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Theme Preset</span>
                    <select name="theme_preset_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                        <option value="">Default preset</option>
                        @foreach ($themePresets as $themePreset)
                            <option value="{{ $themePreset->id }}" @selected((string) old('theme_preset_id', $page->theme_preset_id) === (string) $themePreset->id)>{{ $themePreset->name }}</option>
                        @endforeach
                    </select>
                    @error('theme_preset_id') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                </label>

                <div class="rounded-lg bg-slate-50 p-4 text-sm text-slate-600 dark:bg-gray-950 dark:text-slate-300">
                    <p class="font-medium text-slate-900 dark:text-white">Publish workflow</p>
                    <p class="mt-2">Pages save as draft until you publish them. Publishing creates a version snapshot for rollback visibility.</p>
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            <div class="grid gap-6 lg:grid-cols-3">
                <label class="block">
                    <span class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Header</span>
                    <select name="header_config_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                        <option value="">Default header</option>
                        @foreach ($headerConfigs as $headerConfig)
                            <option value="{{ $headerConfig->id }}" @selected((string) old('header_config_id', $page->header_config_id) === (string) $headerConfig->id)>{{ $headerConfig->code }}</option>
                        @endforeach
                    </select>
                    @error('header_config_id') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Footer</span>
                    <select name="footer_config_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                        <option value="">Default footer</option>
                        @foreach ($footerConfigs as $footerConfig)
                            <option value="{{ $footerConfig->id }}" @selected((string) old('footer_config_id', $page->footer_config_id) === (string) $footerConfig->id)>{{ $footerConfig->code }}</option>
                        @endforeach
                    </select>
                    @error('footer_config_id') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Menu</span>
                    <select name="menu_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                        <option value="">Default menu</option>
                        @foreach ($menus as $menu)
                            <option value="{{ $menu->id }}" @selected((string) old('menu_id', $page->menu_id) === (string) $menu->id)>{{ $menu->name }} ({{ $menu->location }})</option>
                        @endforeach
                    </select>
                    @error('menu_id') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                </label>
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Sections</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Approved section rows define the homepage composition. Ordering is controlled with `sort_order`.</p>
                </div>

                <button type="button" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-950" data-add-section>
                    Add Section
                </button>
            </div>

            @error('sections') <p class="mt-4 text-sm text-red-600">{{ $message }}</p> @enderror

            <div class="mt-6 space-y-4" data-section-list>
                @foreach ($sectionRows as $index => $sectionRow)
                    <div class="rounded-xl border border-slate-200 p-4 dark:border-gray-700" data-section-row>
                        <input type="hidden" name="sections[{{ $index }}][id]" value="{{ $sectionRow['id'] ?? '' }}">

                        <div class="grid gap-4 lg:grid-cols-5">
                            <label class="block">
                                <span class="mb-2 block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Area</span>
                                <select name="sections[{{ $index }}][template_area_id]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white" data-area-select data-selected-value="{{ $sectionRow['template_area_id'] ?? '' }}">
                                    <option value="">Select area</option>
                                </select>
                            </label>

                            <label class="block lg:col-span-2">
                                <span class="mb-2 block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Section Type</span>
                                <select name="sections[{{ $index }}][section_type_id]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white" data-section-type-select>
                                    <option value="">Select section type</option>
                                    @foreach ($sectionTypes as $sectionType)
                                        <option
                                            value="{{ $sectionType->id }}"
                                            data-sources='@json($sectionType->allowed_data_sources_json ?? [])'
                                            @selected((string) ($sectionRow['section_type_id'] ?? '') === (string) $sectionType->id)
                                        >
                                            {{ $sectionType->name }} ({{ $sectionType->code }})
                                        </option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="block lg:col-span-2">
                                <span class="mb-2 block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Title</span>
                                <input name="sections[{{ $index }}][title]" value="{{ $sectionRow['title'] ?? '' }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                            </label>
                        </div>

                        <div class="mt-4 grid gap-4 lg:grid-cols-[160px_minmax(0,1fr)_auto]">
                            <label class="block">
                                <span class="mb-2 block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Sort Order</span>
                                <input type="number" min="0" name="sections[{{ $index }}][sort_order]" value="{{ $sectionRow['sort_order'] ?? 0 }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                            </label>

                            <label class="block">
                                <span class="mb-2 block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Data Source</span>
                                <select name="sections[{{ $index }}][data_source_type]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white" data-data-source-select data-selected-value="{{ $sectionRow['data_source_type'] ?? '' }}">
                                    <option value="">No data source</option>
                                </select>
                            </label>

                            <div class="flex items-end justify-between gap-4">
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                    <input type="hidden" name="sections[{{ $index }}][is_active]" value="0">
                                    <input type="checkbox" name="sections[{{ $index }}][is_active]" value="1" @checked(($sectionRow['is_active'] ?? false))>
                                    Active
                                </label>

                                <button type="button" class="text-sm text-red-600 hover:underline" data-remove-section>Remove</button>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-4 lg:grid-cols-2">
                            <label class="block">
                                <span class="mb-2 block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Settings JSON</span>
                                <textarea name="sections[{{ $index }}][settings_json]" rows="8" class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono text-xs dark:border-gray-700 dark:bg-gray-950 dark:text-white">{{ $sectionRow['settings_json'] ?? '{}' }}</textarea>
                            </label>

                            <label class="block">
                                <span class="mb-2 block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Data Source Payload JSON</span>
                                <textarea name="sections[{{ $index }}][data_source_payload_json]" rows="8" class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono text-xs dark:border-gray-700 dark:bg-gray-950 dark:text-white">{{ $sectionRow['data_source_payload_json'] ?? '{}' }}</textarea>
                            </label>
                        </div>

                        @foreach ($errors->get("sections.$index.*") as $messages)
                            @foreach ($messages as $message)
                                <p class="mt-3 text-xs text-red-600">{{ $message }}</p>
                            @endforeach
                        @endforeach
                    </div>
                @endforeach
            </div>

            <template data-section-template>
                <div class="rounded-xl border border-slate-200 p-4 dark:border-gray-700" data-section-row>
                    <input type="hidden" name="sections[__INDEX__][id]" value="">

                    <div class="grid gap-4 lg:grid-cols-5">
                        <label class="block">
                            <span class="mb-2 block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Area</span>
                            <select name="sections[__INDEX__][template_area_id]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white" data-area-select data-selected-value="">
                                <option value="">Select area</option>
                            </select>
                        </label>

                        <label class="block lg:col-span-2">
                            <span class="mb-2 block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Section Type</span>
                            <select name="sections[__INDEX__][section_type_id]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white" data-section-type-select>
                                <option value="">Select section type</option>
                                @foreach ($sectionTypes as $sectionType)
                                    <option value="{{ $sectionType->id }}" data-sources='@json($sectionType->allowed_data_sources_json ?? [])'>
                                        {{ $sectionType->name }} ({{ $sectionType->code }})
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <label class="block lg:col-span-2">
                            <span class="mb-2 block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Title</span>
                            <input name="sections[__INDEX__][title]" value="" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                        </label>
                    </div>

                    <div class="mt-4 grid gap-4 lg:grid-cols-[160px_minmax(0,1fr)_auto]">
                        <label class="block">
                            <span class="mb-2 block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Sort Order</span>
                            <input type="number" min="0" name="sections[__INDEX__][sort_order]" value="0" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Data Source</span>
                            <select name="sections[__INDEX__][data_source_type]" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white" data-data-source-select data-selected-value="">
                                <option value="">No data source</option>
                            </select>
                        </label>

                        <div class="flex items-end justify-between gap-4">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                <input type="hidden" name="sections[__INDEX__][is_active]" value="0">
                                <input type="checkbox" name="sections[__INDEX__][is_active]" value="1" checked>
                                Active
                            </label>

                            <button type="button" class="text-sm text-red-600 hover:underline" data-remove-section>Remove</button>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-4 lg:grid-cols-2">
                        <label class="block">
                            <span class="mb-2 block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Settings JSON</span>
                            <textarea name="sections[__INDEX__][settings_json]" rows="8" class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono text-xs dark:border-gray-700 dark:bg-gray-950 dark:text-white">{}</textarea>
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Data Source Payload JSON</span>
                            <textarea name="sections[__INDEX__][data_source_payload_json]" rows="8" class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono text-xs dark:border-gray-700 dark:bg-gray-950 dark:text-white">{}</textarea>
                        </label>
                    </div>
                </div>
            </template>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">SEO</h2>

            <div class="mt-6 grid gap-6 lg:grid-cols-2">
                <label class="block">
                    <span class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Meta Title</span>
                    <input name="seo[title]" value="{{ old('seo.title', $page->seoMeta?->title) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Canonical URL</span>
                    <input name="seo[canonical_url]" value="{{ old('seo.canonical_url', $page->seoMeta?->canonical_url) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                </label>
            </div>

            <div class="mt-6 grid gap-6 lg:grid-cols-2">
                <label class="block">
                    <span class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Meta Description</span>
                    <textarea name="seo[description]" rows="5" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white">{{ old('seo.description', $page->seoMeta?->description) }}</textarea>
                </label>

                <div class="space-y-6">
                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Keywords</span>
                        <input name="seo[keywords]" value="{{ old('seo.keywords', $page->seoMeta?->keywords) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Robots</span>
                        <input name="seo[robots]" value="{{ old('seo.robots', $page->seoMeta?->robots) }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                    </label>
                </div>
            </div>

            <label class="mt-6 block">
                <span class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Open Graph JSON</span>
                <textarea name="seo[og_json]" rows="8" class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono text-xs dark:border-gray-700 dark:bg-gray-950 dark:text-white">{{ old('seo.og_json', json_encode($page->seoMeta?->og_json ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) }}</textarea>
            </label>
        </section>

        <div class="flex flex-wrap items-center gap-3">
            <button type="submit" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-gray-900">Save Draft</button>

            @if ($page->exists)
                <a href="{{ route('admin.cms.pages.preview', $page) }}" target="_blank" class="text-sm text-blue-600 hover:underline">Preview</a>

                @if ($page->isPublished())
                    <form method="POST" action="{{ route('admin.cms.pages.unpublish', $page) }}">
                        @csrf
                        <button type="submit" class="text-sm text-amber-600 hover:underline">Unpublish</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.cms.pages.publish', $page) }}">
                        @csrf
                        <button type="submit" class="text-sm text-emerald-600 hover:underline">Publish</button>
                    </form>
                @endif
            @endif
        </div>
    </form>

    <script>
        (() => {
            const editor = document.querySelector('[data-page-editor]');

            if (! editor) {
                return;
            }

            const templateAreasByTemplate = JSON.parse(editor.dataset.templateAreas || '{}');
            const templateSelect = editor.querySelector('[data-template-select]');
            const sectionList = editor.querySelector('[data-section-list]');
            const template = editor.querySelector('[data-section-template]');
            const addButton = editor.querySelector('[data-add-section]');
            let nextIndex = sectionList.querySelectorAll('[data-section-row]').length;

            const populateAreaOptions = (row) => {
                const select = row.querySelector('[data-area-select]');
                const selectedValue = select.dataset.selectedValue || select.value;
                const templateId = templateSelect.value;
                const areas = templateAreasByTemplate[templateId] || [];

                select.innerHTML = '<option value="">Select area</option>';

                areas.forEach((area) => {
                    const option = document.createElement('option');
                    option.value = area.id;
                    option.textContent = `${area.name} (${area.code})`;

                    if (`${selectedValue}` === `${area.id}`) {
                        option.selected = true;
                    }

                    select.appendChild(option);
                });
            };

            const populateDataSources = (row) => {
                const typeSelect = row.querySelector('[data-section-type-select]');
                const sourceSelect = row.querySelector('[data-data-source-select]');
                const selectedOption = typeSelect.options[typeSelect.selectedIndex];
                const selectedValue = sourceSelect.dataset.selectedValue || sourceSelect.value;
                const sources = selectedOption?.dataset.sources ? JSON.parse(selectedOption.dataset.sources) : [];

                sourceSelect.innerHTML = '<option value="">No data source</option>';

                sources.forEach((source) => {
                    const option = document.createElement('option');
                    option.value = source;
                    option.textContent = source;

                    if (selectedValue === source) {
                        option.selected = true;
                    }

                    sourceSelect.appendChild(option);
                });
            };

            const bindRow = (row) => {
                const areaSelect = row.querySelector('[data-area-select]');
                const dataSourceSelect = row.querySelector('[data-data-source-select]');

                populateAreaOptions(row);
                populateDataSources(row);

                row.querySelector('[data-section-type-select]').addEventListener('change', () => populateDataSources(row));
                row.querySelector('[data-remove-section]').addEventListener('click', () => row.remove());
            };

            templateSelect.addEventListener('change', () => {
                sectionList.querySelectorAll('[data-section-row]').forEach((row) => populateAreaOptions(row));
            });

            addButton.addEventListener('click', () => {
                const index = nextIndex++;
                const html = template.innerHTML.replaceAll('__INDEX__', index);
                const wrapper = document.createElement('div');

                wrapper.innerHTML = html.trim();

                const row = wrapper.firstElementChild;

                sectionList.appendChild(row);
                bindRow(row);
            });

            sectionList.querySelectorAll('[data-section-row]').forEach(bindRow);
        })();
    </script>
</x-admin::layouts>
