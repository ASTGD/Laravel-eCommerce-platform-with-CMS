<?php

namespace Platform\ExperienceCms\Http\Requests\Admin;

use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SectionTypeRequest extends JsonFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                      => ['required', 'string', 'max:255'],
            'code'                      => ['nullable', 'string', 'max:255', Rule::unique('section_types', 'code')->ignore($this->route('platformSectionType'))],
            'category'                  => ['required', 'string', 'max:100'],
            'config_schema_json'        => ['nullable', 'string', 'json'],
            'supports_components'       => ['nullable', 'boolean'],
            'allowed_data_sources_json' => ['nullable', 'string', 'json'],
            'renderer_class'            => ['nullable', 'string', 'max:255'],
            'is_active'                 => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => $this->input('code') ?: Str::slug((string) $this->input('name'), '_'),
        ]);
    }

    public function payload(): array
    {
        return [
            'name'                      => $this->validated('name'),
            'code'                      => $this->validated('code'),
            'category'                  => $this->validated('category'),
            'config_schema_json'        => $this->decoded('config_schema_json'),
            'supports_components'       => $this->boolean('supports_components'),
            'allowed_data_sources_json' => $this->decoded('allowed_data_sources_json'),
            'renderer_class'            => $this->validated('renderer_class'),
            'is_active'                 => $this->boolean('is_active', true),
        ];
    }
}
