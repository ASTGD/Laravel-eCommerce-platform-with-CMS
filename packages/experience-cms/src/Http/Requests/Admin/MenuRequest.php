<?php

namespace Platform\ExperienceCms\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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
            'code'      => ['nullable', 'string', 'max:255', Rule::unique('menus', 'code')->ignore($this->route('platformMenu'))],
            'location'  => ['required', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'items'     => ['nullable', 'array'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.title' => ['nullable', 'string', 'max:255'],
            'items.*.type' => ['nullable', 'string', 'max:50'],
            'items.*.target' => ['nullable', 'string', 'max:255'],
            'items.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'items.*.is_active' => ['nullable', 'boolean'],
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

    public function itemsPayload(): array
    {
        return collect($this->input('items', []))
            ->map(function (array $item) {
                return [
                    'id'         => filled($item['id'] ?? null) ? (int) $item['id'] : null,
                    'title'      => trim((string) ($item['title'] ?? '')),
                    'type'       => trim((string) ($item['type'] ?? '')),
                    'target'     => trim((string) ($item['target'] ?? '')),
                    'sort_order' => (int) ($item['sort_order'] ?? 0),
                    'is_active'  => filter_var($item['is_active'] ?? false, FILTER_VALIDATE_BOOLEAN),
                ];
            })
            ->filter(fn (array $item) => $item['title'] !== '' || $item['target'] !== '' || $item['type'] !== '')
            ->values()
            ->all();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $currentMenu = $this->route('platformMenu');

            foreach ($this->itemsPayload() as $index => $item) {
                if ($item['title'] === '' || $item['type'] === '' || $item['target'] === '') {
                    $validator->errors()->add("items.$index", 'Menu items require a title, type, and target.');
                }

                if ($currentMenu && $item['id']) {
                    $exists = $currentMenu->items()->whereKey($item['id'])->exists();

                    if (! $exists) {
                        $validator->errors()->add("items.$index.id", 'Menu item does not belong to this menu.');
                    }
                }
            }
        });
    }
}
