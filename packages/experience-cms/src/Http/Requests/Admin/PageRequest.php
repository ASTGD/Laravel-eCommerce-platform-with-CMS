<?php

namespace Platform\ExperienceCms\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Platform\ExperienceCms\Models\ComponentType;
use Platform\ExperienceCms\Models\SectionType;
use Platform\ExperienceCms\Models\Template;
use Platform\ExperienceCms\Models\TemplateArea;
use Platform\ExperienceCms\Services\ComponentTypeRegistry;
use Platform\ExperienceCms\Services\SectionTypeRegistry;

class PageRequest extends JsonFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'slug'        => ['nullable', 'string', 'max:255', Rule::unique('pages', 'slug')->ignore($this->route('platformPage'))],
            'type'        => ['required', 'string', 'in:homepage,content_page,campaign_page,category_page,product_page'],
            'template_id' => ['nullable', 'integer', 'exists:templates,id'],
            'header_config_id' => ['nullable', 'integer', 'exists:header_configs,id'],
            'footer_config_id' => ['nullable', 'integer', 'exists:footer_configs,id'],
            'menu_id' => ['nullable', 'integer', 'exists:menus,id'],
            'theme_preset_id' => ['nullable', 'integer', 'exists:theme_presets,id'],
            'settings_json' => ['nullable', 'string', 'json'],
            'seo.title' => ['nullable', 'string', 'max:255'],
            'seo.description' => ['nullable', 'string'],
            'seo.keywords' => ['nullable', 'string'],
            'seo.robots' => ['nullable', 'string', 'max:255'],
            'seo.canonical_url' => ['nullable', 'string', 'max:255'],
            'seo.og_json' => ['nullable', 'string', 'json'],
            'sections' => ['nullable', 'array'],
            'sections.*.id' => ['nullable', 'integer'],
            'sections.*.template_area_id' => ['nullable', 'integer', 'exists:template_areas,id'],
            'sections.*.section_type_id' => ['nullable', 'integer', 'exists:section_types,id'],
            'sections.*.title' => ['nullable', 'string', 'max:255'],
            'sections.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'sections.*.is_active' => ['nullable', 'boolean'],
            'sections.*.settings_json' => ['nullable', 'string', 'json'],
            'sections.*.data_source_type' => ['nullable', 'string', 'max:100'],
            'sections.*.data_source_payload_json' => ['nullable', 'string', 'json'],
            'sections.*.components' => ['nullable', 'array'],
            'sections.*.components.*.id' => ['nullable', 'integer'],
            'sections.*.components.*.component_type_id' => ['nullable', 'integer', 'exists:component_types,id'],
            'sections.*.components.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'sections.*.components.*.settings_json' => ['nullable', 'string', 'json'],
            'sections.*.components.*.data_source_type' => ['nullable', 'string', 'max:100'],
            'sections.*.components.*.data_source_payload_json' => ['nullable', 'string', 'json'],
            'sections.*.components.*.is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => $this->input('slug') ?: Str::slug((string) $this->input('title')),
        ]);
    }

    public function payload(): array
    {
        return [
            'title'       => $this->validated('title'),
            'slug'        => $this->validated('slug'),
            'type'        => $this->validated('type'),
            'template_id' => $this->validated('template_id'),
            'header_config_id' => $this->validated('header_config_id'),
            'footer_config_id' => $this->validated('footer_config_id'),
            'menu_id' => $this->validated('menu_id'),
            'theme_preset_id' => $this->validated('theme_preset_id'),
            'settings_json' => $this->pageSettingsPayload(),
        ];
    }

    public function seoPayload(): array
    {
        return [
            'title' => $this->input('seo.title'),
            'description' => $this->input('seo.description'),
            'keywords' => $this->input('seo.keywords'),
            'robots' => $this->input('seo.robots'),
            'canonical_url' => $this->input('seo.canonical_url'),
            'og_json' => $this->decodeValue($this->input('seo.og_json')),
        ];
    }

    public function sectionsPayload(): array
    {
        return collect($this->input('sections', []))
            ->map(function (array $section) {
                return [
                    'id' => filled($section['id'] ?? null) ? (int) $section['id'] : null,
                    'template_area_id' => filled($section['template_area_id'] ?? null) ? (int) $section['template_area_id'] : null,
                    'section_type_id' => filled($section['section_type_id'] ?? null) ? (int) $section['section_type_id'] : null,
                    'sort_order' => (int) ($section['sort_order'] ?? 0),
                    'title' => trim((string) ($section['title'] ?? '')),
                    'settings_json' => $this->decodeValue($section['settings_json'] ?? null),
                    'data_source_type' => trim((string) ($section['data_source_type'] ?? '')) ?: null,
                    'data_source_payload_json' => $this->decodeValue($section['data_source_payload_json'] ?? null),
                    'is_active' => filter_var($section['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'components' => collect($section['components'] ?? [])->map(function (array $component) {
                        return [
                            'id' => filled($component['id'] ?? null) ? (int) $component['id'] : null,
                            'component_type_id' => filled($component['component_type_id'] ?? null) ? (int) $component['component_type_id'] : null,
                            'sort_order' => (int) ($component['sort_order'] ?? 0),
                            'settings_json' => $this->decodeValue($component['settings_json'] ?? null),
                            'data_source_type' => trim((string) ($component['data_source_type'] ?? '')) ?: null,
                            'data_source_payload_json' => $this->decodeValue($component['data_source_payload_json'] ?? null),
                            'is_active' => filter_var($component['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN),
                        ];
                    })->filter(function (array $component) {
                        return $component['component_type_id']
                            || $component['settings_json'] !== []
                            || $component['data_source_type']
                            || $component['data_source_payload_json'] !== [];
                    })->values()->all(),
                ];
            })
            ->filter(function (array $section) {
                return $section['section_type_id']
                    || $section['title'] !== ''
                    || $section['settings_json'] !== []
                    || $section['data_source_type']
                    || $section['data_source_payload_json'] !== [];
            })
            ->values()
            ->all();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $sections = $this->sectionsPayload();
            $template = $this->validated('template_id')
                ? Template::query()->with('areas')->find($this->validated('template_id'))
                : null;
            $currentPage = $this->route('platformPage');

            if ($sections !== [] && ! $template) {
                $validator->errors()->add('template_id', 'Pages must have a template before sections can be configured.');

                return;
            }

            $sectionTypes = SectionType::query()
                ->whereIn('id', collect($sections)->pluck('section_type_id')->filter()->all())
                ->get()
                ->keyBy('id');

            $registry = app(SectionTypeRegistry::class);
            $componentRegistry = app(ComponentTypeRegistry::class);
            $validAreaIds = $template?->areas->pluck('id')->map(fn ($id) => (int) $id)->all() ?? [];
            $componentTypes = ComponentType::query()
                ->whereIn(
                    'id',
                    collect($sections)->flatMap(fn (array $section) => collect($section['components'] ?? [])->pluck('component_type_id'))->filter()->all()
                )
                ->get()
                ->keyBy('id');

            foreach ($sections as $index => $section) {
                if (! $section['section_type_id']) {
                    $validator->errors()->add("sections.$index.section_type_id", 'A section type is required.');

                    continue;
                }

                if ($template && count($validAreaIds) > 1 && ! $section['template_area_id']) {
                    $validator->errors()->add("sections.$index.template_area_id", 'A template area is required for this section.');
                }

                if ($currentPage && $section['id']) {
                    $exists = $currentPage->sections()->whereKey($section['id'])->exists();

                    if (! $exists) {
                        $validator->errors()->add("sections.$index.id", 'Section does not belong to this page.');
                    }
                }

                if ($section['template_area_id'] && ! in_array($section['template_area_id'], $validAreaIds, true)) {
                    $validator->errors()->add("sections.$index.template_area_id", 'Selected template area is not available for this template.');
                }

                $sectionType = $sectionTypes->get($section['section_type_id']);

                if (! $sectionType) {
                    $validator->errors()->add("sections.$index.section_type_id", 'Selected section type could not be found.');

                    continue;
                }

                $definition = $registry->find($sectionType->code);

                if (! $definition) {
                    continue;
                }

                if (
                    $section['data_source_type']
                    && ! in_array($section['data_source_type'], $definition->allowedDataSources(), true)
                ) {
                    $validator->errors()->add("sections.$index.data_source_type", 'The selected data source is not allowed for this section type.');
                }

                $templateArea = $section['template_area_id']
                    ? $template?->areas->firstWhere('id', $section['template_area_id'])
                    : null;

                $allowedSectionCodes = Arr::wrap($templateArea?->rules_json['allowed_section_codes'] ?? []);

                if (
                    $allowedSectionCodes !== []
                    && ! in_array($sectionType->code, $allowedSectionCodes, true)
                ) {
                    $validator->errors()->add("sections.$index.section_type_id", 'This section type is not allowed in the selected template area.');
                }

                $settingsValidator = ValidatorFacade::make(
                    array_replace($definition->defaultConfig(), $section['settings_json']),
                    $definition->validationRules()
                );

                if ($settingsValidator->fails()) {
                    foreach ($settingsValidator->errors()->all() as $message) {
                        $validator->errors()->add("sections.$index.settings_json", $message);
                    }
                }

                if (! $definition->supportsComponents() && ($section['components'] ?? []) !== []) {
                    $validator->errors()->add("sections.$index.components", 'This section type does not support nested components.');
                }

                foreach ($section['components'] ?? [] as $componentIndex => $component) {
                    if (! $component['component_type_id']) {
                        $validator->errors()->add("sections.$index.components.$componentIndex.component_type_id", 'A component type is required.');

                        continue;
                    }

                    $componentType = $componentTypes->get($component['component_type_id']);

                    if (! $componentType) {
                        $validator->errors()->add("sections.$index.components.$componentIndex.component_type_id", 'Selected component type could not be found.');

                        continue;
                    }

                    $componentDefinition = $componentRegistry->find($componentType->code);

                    if (! $componentDefinition) {
                        continue;
                    }

                    $componentSettingsValidator = ValidatorFacade::make(
                        array_replace($componentDefinition->defaultConfig(), $component['settings_json']),
                        $componentDefinition->validationRules()
                    );

                    if ($componentSettingsValidator->fails()) {
                        foreach ($componentSettingsValidator->errors()->all() as $message) {
                            $validator->errors()->add("sections.$index.components.$componentIndex.settings_json", $message);
                        }
                    }
                }
            }

            $pageSettingsValidator = ValidatorFacade::make(
                $this->pageSettingsPayload(),
                $this->pageSettingsRules()
            );

            if ($pageSettingsValidator->fails()) {
                foreach ($pageSettingsValidator->errors()->all() as $message) {
                    $validator->errors()->add('settings_json', $message);
                }
            }

            if ($template && $template->page_type !== $this->validated('type')) {
                $validator->errors()->add('template_id', 'The selected template does not match the page type.');
            }
        });
    }

    protected function pageSettingsPayload(): array
    {
        return $this->decodeValue($this->input('settings_json'));
    }

    protected function pageSettingsRules(): array
    {
        return match ($this->input('type')) {
            'category_page' => [
                'listing.default_mode' => ['nullable', 'in:grid,list'],
                'listing.limit' => ['nullable', 'integer', 'min:1', 'max:48'],
                'listing.show_toolbar' => ['nullable', 'boolean'],
                'listing.show_description' => ['nullable', 'boolean'],
                'listing.empty_state_heading' => ['nullable', 'string', 'max:255'],
            ],
            'product_page' => [
                'related.limit' => ['nullable', 'integer', 'min:1', 'max:12'],
                'shipping_note' => ['nullable', 'string'],
            ],
            default => [],
        };
    }
}
