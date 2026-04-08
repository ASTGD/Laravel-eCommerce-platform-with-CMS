<?php

declare(strict_types=1);

namespace ExperienceCms\Http\Requests\Admin;

use ExperienceCms\Models\SectionType;
use ExperienceCms\Services\SectionRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PageSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $sectionType = SectionType::query()->find($this->input('section_type_id'));
        $definition = $sectionType !== null
            ? app(SectionRegistry::class)->find($sectionType->code)
            : null;

        $settings = $this->input('settings_json', []);

        if (is_array($settings) && $definition !== null) {
            foreach ($definition->configSchema() as $field) {
                if (($field['type'] ?? null) !== 'json') {
                    continue;
                }

                $key = (string) ($field['key'] ?? '');
                $value = $settings[$key] ?? null;

                if (! is_string($value)) {
                    continue;
                }

                $trimmed = trim($value);
                $settings[$key] = $trimmed === '' ? [] : json_decode($trimmed, true) ?? $value;
            }
        }

        $payload = $this->input('data_source_payload_json');

        if (is_string($payload)) {
            $trimmed = trim($payload);
            $payload = $trimmed === '' ? [] : json_decode($trimmed, true) ?? $payload;
        }

        $this->merge([
            'settings_json' => $settings,
            'data_source_payload_json' => $payload,
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        $sectionType = SectionType::query()->find($this->input('section_type_id'));
        $definition = $sectionType !== null
            ? app(SectionRegistry::class)->find($sectionType->code)
            : null;

        $rules = [
            'section_type_id' => ['required', 'exists:section_types,id'],
            'title' => ['nullable', 'string', 'max:120'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'settings_json' => ['nullable', 'array'],
            'data_source_type' => ['nullable', 'string', Rule::in($definition?->supportedDataSources() ?? [])],
            'data_source_payload_json' => ['nullable', 'array'],
            'is_active' => ['required', 'boolean'],
        ];

        foreach ($definition?->validationRules() ?? [] as $key => $fieldRules) {
            $rules["settings_json.$key"] = $fieldRules;
        }

        return $rules;
    }
}
