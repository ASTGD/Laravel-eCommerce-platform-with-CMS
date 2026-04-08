<?php

declare(strict_types=1);

namespace ExperienceCms\Support;

trait ParsesJsonFields
{
    /**
     * @param  array<int, string>  $fields
     */
    protected function parseJsonFields(array $fields): void
    {
        foreach ($fields as $field) {
            $value = $this->input($field);

            if (! is_string($value)) {
                continue;
            }

            $trimmed = trim($value);

            if ($trimmed === '') {
                $this->merge([$field => []]);

                continue;
            }

            $decoded = json_decode($trimmed, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $this->merge([$field => $decoded]);
            }
        }
    }
}
