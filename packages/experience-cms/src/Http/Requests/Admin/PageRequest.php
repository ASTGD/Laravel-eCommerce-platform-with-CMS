<?php

namespace Platform\ExperienceCms\Http\Requests\Admin;

use Illuminate\Support\Str;

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
            'slug'        => ['nullable', 'string', 'max:255'],
            'type'        => ['required', 'string', 'max:100'],
            'template_id' => ['nullable', 'integer'],
            'seo_meta_id' => ['nullable', 'integer'],
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
            'seo_meta_id' => $this->validated('seo_meta_id'),
        ];
    }
}
