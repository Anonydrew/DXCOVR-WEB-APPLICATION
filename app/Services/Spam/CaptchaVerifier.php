<?php

namespace App\Services\Spam;

interface CaptchaVerifier
{
    public function enabled(): bool;

    /**
     * Name of the form field carrying the challenge response.
     */
    public function responseField(): string;

    public function verify(?string $response, ?string $ip): bool;
}
