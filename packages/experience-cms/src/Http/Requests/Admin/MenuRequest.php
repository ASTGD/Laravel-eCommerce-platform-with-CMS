<?php

declare(strict_types=1);

namespace ExperienceCms\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    public function rules(): array
    {
        $menuId = $this->route('menu');

        return [
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:120', Rule::unique('menus', 'code')->ignore($menuId)],
            'location' => ['nullable', 'string', 'max:80'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
