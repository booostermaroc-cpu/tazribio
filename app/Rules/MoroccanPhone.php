<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MoroccanPhone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/^(?:\+212|0)[5-7]\d{8}$/', preg_replace('/\s+/', '', $value))) {
            $fail('The :attribute must be a valid Moroccan phone number (e.g. 0612345678 or +212612345678).');
        }
    }
}
