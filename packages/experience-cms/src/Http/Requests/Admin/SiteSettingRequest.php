<?php

namespace Platform\ExperienceCms\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class SiteSettingRequest extends JsonFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'key' => ['required', 'string', Rule::in([
                'store.identity',
                'store.contact',
                'store.social_links',
                'store.trust_badges',
                'store.category_page',
                'store.product_page',
            ])],
            'group' => ['required', 'string', 'max:100'],
            'value_json' => ['nullable', 'string', 'json'],
        ];
    }

    public function payload(): array
    {
        return [
            'key' => $this->validated('key'),
            'group' => $this->validated('group'),
            'value_json' => $this->decoded('value_json'),
        ];
    }
}
