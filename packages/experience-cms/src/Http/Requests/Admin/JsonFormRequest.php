<?php

namespace Platform\ExperienceCms\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

abstract class JsonFormRequest extends FormRequest
{
    protected function decoded(string $key, array $default = []): array
    {
        return $this->decodeValue($this->validated($key), $default);
    }

    protected function decodeValue(mixed $value, array $default = []): array
    {
        if ($value === null || $value === '') {
            return $default;
        }

        if (is_array($value)) {
            return $value;
        }

        return (array) json_decode((string) $value, true);
    }
}
