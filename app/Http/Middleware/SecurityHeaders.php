<?php

namespace App\Http\Middleware;

use App\Services\Spam\CaptchaVerifier;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies HTTP security headers and a nonce-based Content Security Policy.
 *
 * The public site runs without 'unsafe-eval'. The authenticated admin allows
 * it because Alpine.js evaluates its inline expressions at runtime.
 */
class SecurityHeaders
{
    public function __construct(protected CaptchaVerifier $captcha) {}

    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Str::random(32);
        Vite::useCspNonce($nonce);
        $request->attributes->set('csp_nonce', $nonce);

        $response = $next($request);

        $headers = $response->headers;
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $headers->remove('X-Powered-By');

        if (config('dxcovr.security.hsts') && $request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if (config('dxcovr.security.csp') && ! $headers->has('Content-Security-Policy')) {
            $headers->set('Content-Security-Policy', $this->policy($request, $nonce));
        }

        return $response;
    }

    protected function policy(Request $request, string $nonce): string
    {
        $isAdmin = $request->is('admin', 'admin/*');
        $scripts = ["'self'", "'nonce-{$nonce}'"];
        $frames = ["'self'", 'https://www.youtube-nocookie.com', 'https://www.youtube.com', 'https://player.vimeo.com'];
        $connect = ["'self'"];

        if ($isAdmin) {
            $scripts[] = "'unsafe-eval'";
        }

        if ($this->captcha->enabled()) {
            $scripts[] = 'https://challenges.cloudflare.com';
            $frames[] = 'https://challenges.cloudflare.com';
        }

        if (Vite::isRunningHot()) {
            $hot = rtrim((string) file_get_contents(Vite::hotFile()));
            $scripts[] = $hot;
            $connect[] = $hot;
            $connect[] = str_replace(['http://', 'https://'], ['ws://', 'wss://'], $hot);
        }

        return implode('; ', [
            "default-src 'self'",
            'script-src '.implode(' ', $scripts),
            "style-src 'self' 'unsafe-inline'".(Vite::isRunningHot() ? ' '.rtrim((string) file_get_contents(Vite::hotFile())) : ''),
            "img-src 'self' data: blob: https:",
            "font-src 'self' data:",
            "media-src 'self' https:",
            'connect-src '.implode(' ', $connect),
            'frame-src '.implode(' ', $frames),
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ]);
    }
}
