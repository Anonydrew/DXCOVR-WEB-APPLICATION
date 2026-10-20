<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Accepts site-relative paths ("/contact", "#section"), http(s) URLs, mailto: and tel: links.
 * Rejects javascript:, data: and any other scheme that could execute code.
 */
class SafeUrl implements ValidationRule
{
    public function __construct(protected bool $allowRelative = true) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value) || preg_match('/[\s<>"\'`]/', $value)) {
            $fail('The :attribute must be a valid link.');

            return;
        }

        if ($this->allowRelative && (str_starts_with($value, '/') && ! str_starts_with($value, '//') || str_starts_with($value, '#'))) {
            return;
        }

        if (preg_match('/^mailto:[^@\s]+@[^@\s]+$/i', $value) || preg_match('/^tel:\+?[0-9 ()-]{5,20}$/i', $value)) {
            return;
        }

        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));

        if (! in_array($scheme, ['http', 'https'], true) || filter_var($value, FILTER_VALIDATE_URL) === false) {
            $fail($this->allowRelative
                ? 'The :attribute must start with "/" for a page on this site, or be a full https:// address.'
                : 'The :attribute must be a full https:// address.');
        }
    }
}
