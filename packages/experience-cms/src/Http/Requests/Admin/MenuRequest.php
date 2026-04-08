<?php

namespace Platform\ExperienceCms\Http\Requests\Admin;

use Illuminate\Support\Str;

class MenuRequest extends JsonFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            'code'      => ['nullable', 'string', 'max:255'],
            'location'  => ['required', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
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
            'name'      => $this->validated('name'),
            'code'      => $this->validated('code'),
            'location'  => $this->validated('location'),
            'is_active' => $this->boolean('is_active', true),
        ];
    }
}
