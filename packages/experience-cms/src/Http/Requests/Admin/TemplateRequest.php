<?php

namespace Platform\ExperienceCms\Http\Requests\Admin;

use Illuminate\Support\Str;

class TemplateRequest extends JsonFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'code'        => ['nullable', 'string', 'max:255'],
            'page_type'   => ['required', 'string', 'max:100'],
            'schema_json' => ['nullable', 'string', 'json'],
            'is_active'   => ['nullable', 'boolean'],
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
            'name'        => $this->validated('name'),
            'code'        => $this->validated('code'),
            'page_type'   => $this->validated('page_type'),
            'schema_json' => $this->decoded('schema_json'),
            'is_active'   => $this->boolean('is_active', true),
        ];
    }
}
