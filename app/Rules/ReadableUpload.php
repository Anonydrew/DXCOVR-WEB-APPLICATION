<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Throwable;

/**
 * Fails cleanly when an upload cannot be read or its type cannot be detected
 * (for example when antivirus software locks the temporary file), instead
 * of letting later content-type checks throw a server error.
 */
class ReadableUpload implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail('The :attribute could not be uploaded. Please try again.');

            return;
        }

        try {
            $mime = @$value->getMimeType();
        } catch (Throwable) {
            $mime = null;
        }

        if (! is_string($mime) || $mime === '') {
            $fail('The :attribute could not be read. It may be damaged or blocked by security software.');
        }
    }
}
