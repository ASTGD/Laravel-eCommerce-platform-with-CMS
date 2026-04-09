<?php

namespace Platform\ExperienceCms\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;
use Platform\ExperienceCms\Models\Page;
use Platform\ExperienceCms\Models\PageAssignment;
use Webkul\Category\Models\Category;
use Webkul\Product\Models\ProductFlat;

class PageAssignmentRequest extends JsonFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page_id' => ['required', 'integer', 'exists:pages,id'],
            'page_type' => ['required', 'string', 'in:category_page,product_page'],
            'scope_type' => ['required', 'string', 'in:global,entity'],
            'entity_id' => ['nullable', 'integer', 'min:1'],
            'priority' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function payload(): array
    {
        $pageType = $this->validated('page_type');

        return [
            'page_id' => (int) $this->validated('page_id'),
            'page_type' => $pageType,
            'scope_type' => $this->validated('scope_type'),
            'entity_type' => $pageType === 'category_page'
                ? PageAssignment::ENTITY_CATEGORY
                : PageAssignment::ENTITY_PRODUCT,
            'entity_id' => $this->validated('scope_type') === PageAssignment::SCOPE_ENTITY
                ? (int) $this->validated('entity_id')
                : null,
            'priority' => (int) ($this->validated('priority') ?? 0),
            'is_active' => $this->boolean('is_active', true),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $page = Page::query()->find($this->validated('page_id'));

            if ($page && $page->type !== $this->validated('page_type')) {
                $validator->errors()->add('page_id', 'The selected page type does not match this assignment type.');
            }

            if ($this->validated('scope_type') !== PageAssignment::SCOPE_ENTITY) {
                return;
            }

            $entityId = (int) $this->validated('entity_id');

            if ($this->validated('page_type') === 'category_page' && ! Category::query()->whereKey($entityId)->exists()) {
                $validator->errors()->add('entity_id', 'The selected category could not be found.');
            }

            if ($this->validated('page_type') === 'product_page' && ! ProductFlat::query()->where('product_id', $entityId)->exists()) {
                $validator->errors()->add('entity_id', 'The selected product could not be found in the current storefront index.');
            }
        });
    }
}
