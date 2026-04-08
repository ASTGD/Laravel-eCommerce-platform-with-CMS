<?php

declare(strict_types=1);

namespace ExperienceCms\Http\Requests\Admin;

use ExperienceCms\Support\ParsesJsonFields;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MenuItemRequest extends FormRequest
{
    use ParsesJsonFields;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->parseJsonFields(['settings_json']);
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    public function rules(): array
    {
        $itemId = $this->route('item');

        return [
            'parent_id' => ['nullable', 'exists:menu_items,id', Rule::notIn([$itemId])],
            'title' => ['required', 'string', 'max:120'],
            'type' => ['required', 'string', 'max:40'],
            'target' => ['required', 'string', 'max:255'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'settings_json' => ['nullable', 'array'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
