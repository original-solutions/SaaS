<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SafeHtml implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a string.');

            return;
        }

        // Check for script tags
        if (preg_match('/<script\b[^>]*>.*?<\/script>/is', $value)) {
            $fail('The :attribute contains disallowed HTML tags.');

            return;
        }

        // Check for event handlers
        if (preg_match('/\s+on\w+\s*=/i', $value)) {
            $fail('The :attribute contains disallowed HTML attributes.');

            return;
        }

        // Check for javascript: URIs
        if (preg_match('/(href|src)\s*=\s*["\']?javascript:/i', $value)) {
            $fail('The :attribute contains disallowed URI schemes.');
        }
    }
}
