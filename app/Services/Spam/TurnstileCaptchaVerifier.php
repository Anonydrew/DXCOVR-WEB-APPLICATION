<?php

namespace App\Services\Spam;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Cloudflare Turnstile adapter. Enable with CAPTCHA_DRIVER=turnstile,
 * TURNSTILE_SITE_KEY and TURNSTILE_SECRET_KEY.
 */
class TurnstileCaptchaVerifier implements CaptchaVerifier
{
    protected const ENDPOINT = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function __construct(protected ?string $siteKey, protected ?string $secretKey) {}

    public function enabled(): bool
    {
        return filled($this->siteKey) && filled($this->secretKey);
    }

    public function siteKey(): ?string
    {
        return $this->siteKey;
    }

    public function responseField(): string
    {
        return 'cf-turnstile-response';
    }

    public function verify(?string $response, ?string $ip): bool
    {
        if (blank($response)) {
            return false;
        }

        try {
            $result = Http::asForm()->timeout(8)->post(self::ENDPOINT, array_filter([
                'secret' => $this->secretKey,
                'response' => $response,
                'remoteip' => $ip,
            ]));

            return (bool) $result->json('success', false);
        } catch (Throwable $exception) {
            Log::warning('Turnstile verification failed.', ['error' => $exception->getMessage()]);

            return false;
        }
    }
}
