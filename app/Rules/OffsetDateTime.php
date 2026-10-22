<?php

namespace App\Rules;

use Closure;
use DateTimeImmutable;
use Illuminate\Contracts\Validation\ValidationRule;

class OffsetDateTime implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:Z|[+-](?:0\d|1[0-4]):[0-5]\d)\z/', $value)) {
            $fail('The :attribute must be an ISO 8601 date with a timezone.');

            return;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:sP', $value);
        $errors = DateTimeImmutable::getLastErrors();

        if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            $fail('The :attribute must be a valid date.');
        }
    }
}
