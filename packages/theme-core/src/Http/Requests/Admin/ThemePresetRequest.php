<?php

namespace Platform\ThemeCore\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ThemePresetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:255'],
            'code'         => ['nullable', 'string', 'max:255', Rule::unique('theme_presets', 'code')->ignore($this->route('platformThemePreset'))],
            'tokens_json'  => ['nullable', 'string', 'json'],
            'settings_json'=> ['nullable', 'string', 'json'],
            'is_default'   => ['nullable', 'boolean'],
            'is_active'    => ['nullable', 'boolean'],
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
            'name'          => $this->validated('name'),
            'code'          => $this->validated('code'),
            'tokens_json'   => $this->decoded('tokens_json'),
            'settings_json' => $this->decoded('settings_json'),
            'is_default'    => $this->boolean('is_default'),
            'is_active'     => $this->boolean('is_active', true),
        ];
    }

    protected function decoded(string $key): array
    {
        $value = $this->validated($key);

        return $value ? (array) json_decode($value, true) : [];
    }
}
