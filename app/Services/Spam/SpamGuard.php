<?php

namespace App\Services\Spam;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Layered bot protection for public forms: a honeypot field, a signed
 * render timestamp (minimum completion time) and an optional CAPTCHA.
 * Rate limiting is applied separately through route middleware.
 */
class SpamGuard
{
    public const HONEYPOT_FIELD = 'company_website';

    public const TIMESTAMP_FIELD = '_form_started';

    public function __construct(protected CaptchaVerifier $captcha) {}

    public function captcha(): CaptchaVerifier
    {
        return $this->captcha;
    }

    public function timestampToken(): string
    {
        return Crypt::encryptString((string) now()->getTimestamp());
    }

    /**
     * @throws ValidationException
     */
    public function check(Request $request, string $form): void
    {
        if (filled($request->input(self::HONEYPOT_FIELD))) {
            $this->reject($request, $form, 'honeypot');
        }

        try {
            $startedAt = (int) Crypt::decryptString((string) $request->input(self::TIMESTAMP_FIELD));
        } catch (DecryptException) {
            $this->reject($request, $form, 'missing_timestamp');
        }

        $elapsed = now()->getTimestamp() - $startedAt;

        if ($elapsed < (int) config('dxcovr.spam.minimum_seconds', 3) || $elapsed > 60 * 60 * 24) {
            $this->reject($request, $form, 'timing');
        }

        if ($this->captcha->enabled() && ! $this->captcha->verify($request->input($this->captcha->responseField()), $request->ip())) {
            throw ValidationException::withMessages([
                'captcha' => 'Please complete the verification challenge and try again.',
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    protected function reject(Request $request, string $form, string $reason): never
    {
        Log::info('Spam protection rejected a form submission.', ['form' => $form, 'reason' => $reason, 'ip' => $request->ip()]);

        throw ValidationException::withMessages([
            'form' => 'We could not verify this submission. Please reload the page and try again.',
        ]);
    }
}
