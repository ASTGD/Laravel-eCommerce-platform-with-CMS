<?php

declare(strict_types=1);

namespace ExperienceCms\Http\Requests\Admin;

use ExperienceCms\Enums\PageStatus;
use ExperienceCms\Enums\PageType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $pageId = $this->route('page');

        return [
            'title' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:120', Rule::unique('pages', 'slug')->ignore($pageId)],
            'type' => ['required', Rule::enum(PageType::class)],
            'template_id' => ['required', 'exists:templates,id'],
            'status' => ['required', Rule::enum(PageStatus::class)],
        ];
    }
}
