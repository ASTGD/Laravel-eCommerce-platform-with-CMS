<?php

declare(strict_types=1);

namespace ExperienceCms\Http\Requests\Admin;

use ExperienceCms\Support\ParsesJsonFields;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SectionTypeRequest extends FormRequest
{
    use ParsesJsonFields;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->parseJsonFields(['config_schema_json', 'allowed_data_sources_json']);
        $this->merge([
            'supports_components' => $this->boolean('supports_components'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        $sectionTypeId = $this->route('section_type');

        return [
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:120', Rule::unique('section_types', 'code')->ignore($sectionTypeId)],
            'category' => ['required', 'string', 'max:80'],
            'config_schema_json' => ['nullable', 'array'],
            'supports_components' => ['required', 'boolean'],
            'allowed_data_sources_json' => ['nullable', 'array'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
