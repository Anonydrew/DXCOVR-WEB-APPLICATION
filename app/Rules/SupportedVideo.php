<?php

namespace App\Rules;

use App\Support\VideoEmbed;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SupportedVideo implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value) || ! VideoEmbed::isSupported($value)) {
            $fail('The :attribute must be a YouTube or Vimeo link.');
        }
    }
}
