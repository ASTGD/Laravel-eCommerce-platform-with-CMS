<?php

namespace Platform\ExperienceCms\Http\Requests\Admin;

use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ContentEntryRequest extends JsonFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:marketing_copy,faq,trust_badges'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('content_entries', 'slug')->ignore($this->route('platformContentEntry'))],
            'body_json' => ['nullable', 'string', 'json'],
            'status' => ['required', 'string', 'in:draft,published'],
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
            'type' => $this->validated('type'),
            'title' => $this->validated('title'),
            'slug' => $this->validated('slug'),
            'body_json' => $this->decoded('body_json'),
            'status' => $this->validated('status'),
        ];
    }
}
