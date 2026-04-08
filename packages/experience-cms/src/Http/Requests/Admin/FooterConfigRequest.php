<?php

namespace Platform\ExperienceCms\Http\Requests\Admin;

use Illuminate\Support\Str;

class FooterConfigRequest extends JsonFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code'          => ['nullable', 'string', 'max:255'],
            'settings_json' => ['nullable', 'string', 'json'],
            'is_default'    => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => $this->input('code') ?: Str::slug('footer_'.now()->format('YmdHis'), '_'),
        ]);
    }

    public function payload(): array
    {
        return [
            'code'          => $this->validated('code'),
            'settings_json' => $this->decoded('settings_json'),
            'is_default'    => $this->boolean('is_default'),
        ];
    }
}
