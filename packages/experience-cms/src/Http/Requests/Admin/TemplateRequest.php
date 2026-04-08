<?php

declare(strict_types=1);

namespace ExperienceCms\Http\Requests\Admin;

use ExperienceCms\Support\ParsesJsonFields;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TemplateRequest extends FormRequest
{
    use ParsesJsonFields;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->parseJsonFields(['schema_json']);
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    public function rules(): array
    {
        $templateId = $this->route('template');

        return [
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:120', Rule::unique('templates', 'code')->ignore($templateId)],
            'page_type' => ['required', 'string', 'max:80'],
            'schema_json' => ['nullable', 'array'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
