<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rate limits Fortify's password reset endpoints, which have no limiter of their own.
 */
class ThrottlePasswordResets
{
    protected const MAX_ATTEMPTS = 5;

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('POST') || ! $request->routeIs('password.email', 'password.update')) {
            return $next($request);
        }

        $key = 'password-reset:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);

            return back()->withInput($request->only('email'))->withErrors([
                'email' => "Too many attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        RateLimiter::hit($key, 60);

        return $next($request);
    }
}
