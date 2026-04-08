<?php

namespace Platform\ExperienceCms\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

abstract class JsonFormRequest extends FormRequest
{
    protected function decoded(string $key, array $default = []): array
    {
        $value = $this->validated($key);

        if ($value === null || $value === '') {
            return $default;
        }

        return (array) json_decode($value, true);
    }
}
