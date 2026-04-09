<?php

namespace Platform\ExperienceCms\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Platform\ExperienceCms\Models\SectionType;
use Platform\ExperienceCms\Models\Template;
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
            $validAreaIds = $template?->areas->pluck('id')->map(fn ($id) => (int) $id)->all() ?? [];

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

                $settingsValidator = ValidatorFacade::make(
                    array_replace($definition->defaultConfig(), $section['settings_json']),
                    $definition->validationRules()
                );

                if ($settingsValidator->fails()) {
                    foreach ($settingsValidator->errors()->all() as $message) {
                        $validator->errors()->add("sections.$index.settings_json", $message);
                    }
                }
            }
        });
    }
}
