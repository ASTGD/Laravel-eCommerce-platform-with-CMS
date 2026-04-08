<?php

declare(strict_types=1);

namespace ExperienceCms\Http\Requests\Admin;

use ExperienceCms\Support\ParsesJsonFields;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ThemePresetRequest extends FormRequest
{
    use ParsesJsonFields;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->parseJsonFields(['tokens_json', 'settings_json']);
        $this->merge([
            'is_default' => $this->boolean('is_default'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        $themePresetId = $this->route('theme_preset');

        return [
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:120', Rule::unique('theme_presets', 'code')->ignore($themePresetId)],
            'tokens_json' => ['nullable', 'array'],
            'settings_json' => ['nullable', 'array'],
            'is_default' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
