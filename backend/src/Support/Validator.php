<?php

declare(strict_types=1);

namespace FamilyLife\Backend\Support;

final class Validator
{
    public function requiredString(array $body, string $field, int $maxLength = 120): string
    {
        $value = trim((string)($body[$field] ?? ''));
        if ($value === '') {
            throw new ApiException($field . ' is required', 422);
        }
        if (strlen($value) > $maxLength) {
            throw new ApiException($field . ' is too long', 422);
        }

        return $value;
    }

    public function positiveInt(array $body, string $field): int
    {
        $value = $body[$field] ?? null;
        if (!is_numeric($value) || (int)$value <= 0) {
            throw new ApiException($field . ' must be a positive integer', 422);
        }

        return (int)$value;
    }

    public function nonNegativeInt(mixed $value, string $field): int
    {
        if ($value === null || filter_var($value, FILTER_VALIDATE_INT) === false || (int)$value < 0) {
            throw new ApiException($field . ' must be a non-negative integer', 422);
        }

        return (int)$value;
    }
}
