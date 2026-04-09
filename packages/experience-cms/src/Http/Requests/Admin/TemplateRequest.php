<?php

namespace Platform\ExperienceCms\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TemplateRequest extends JsonFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'code'        => ['nullable', 'string', 'max:255', Rule::unique('templates', 'code')->ignore($this->route('platformTemplate'))],
            'page_type'   => ['required', 'string', 'max:100'],
            'schema_json' => ['nullable', 'string', 'json'],
            'is_active'   => ['nullable', 'boolean'],
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
            'name'        => $this->validated('name'),
            'code'        => $this->validated('code'),
            'page_type'   => $this->validated('page_type'),
            'schema_json' => $this->schemaPayload(),
            'is_active'   => $this->boolean('is_active', true),
        ];
    }

    public function areasPayload(): array
    {
        $areas = collect($this->areasPayloadFromDecoded($this->decoded('schema_json')))
            ->map(function (array $area, int $index) {
                return [
                    'code'       => Str::slug((string) ($area['code'] ?? $area['name'] ?? 'area_'.$index), '_'),
                    'name'       => (string) ($area['name'] ?? Str::headline((string) ($area['code'] ?? 'Content'))),
                    'rules_json' => Arr::wrap($area['rules'] ?? $area['rules_json'] ?? []),
                    'sort_order' => (int) ($area['sort_order'] ?? $index + 1),
                ];
            })
            ->filter(fn (array $area) => $area['code'] !== '' && $area['name'] !== '')
            ->values()
            ->all();

        return $areas !== []
            ? $areas
            : [[
                'code'       => 'content',
                'name'       => 'Content',
                'rules_json' => ['max_sections' => 12],
                'sort_order' => 1,
            ]];
    }

    public function schemaPayload(): array
    {
        $schema = $this->decoded('schema_json');
        $schema['areas'] = $this->areasPayloadFromDecoded($schema);

        return $schema;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $schemaValidator = ValidatorFacade::make(
                ['areas' => $this->areasPayload()],
                [
                    'areas' => ['array', 'min:1'],
                    'areas.*.code' => ['required', 'string', 'max:100'],
                    'areas.*.name' => ['required', 'string', 'max:255'],
                    'areas.*.sort_order' => ['required', 'integer', 'min:1'],
                ]
            );

            if ($schemaValidator->fails()) {
                foreach ($schemaValidator->errors()->all() as $message) {
                    $validator->errors()->add('schema_json', $message);
                }
            }
        });
    }

    protected function areasPayloadFromDecoded(array $schema): array
    {
        return collect($schema['areas'] ?? [])
            ->map(function (array $area, int $index) {
                return [
                    'code'       => Str::slug((string) ($area['code'] ?? $area['name'] ?? 'area_'.$index), '_'),
                    'name'       => (string) ($area['name'] ?? Str::headline((string) ($area['code'] ?? 'Content'))),
                    'rules'      => Arr::wrap($area['rules'] ?? $area['rules_json'] ?? []),
                    'sort_order' => (int) ($area['sort_order'] ?? $index + 1),
                ];
            })
            ->filter(fn (array $area) => $area['code'] !== '' && $area['name'] !== '')
            ->values()
            ->all();
    }
}
