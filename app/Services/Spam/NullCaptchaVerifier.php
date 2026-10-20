<?php

namespace App\Services\Spam;

/**
 * Used when no CAPTCHA provider is configured. Honeypot, timing and rate limits still apply.
 */
class NullCaptchaVerifier implements CaptchaVerifier
{
    public function enabled(): bool
    {
        return false;
    }

    public function responseField(): string
    {
        return 'captcha_response';
    }

    public function verify(?string $response, ?string $ip): bool
    {
        return true;
    }
}
