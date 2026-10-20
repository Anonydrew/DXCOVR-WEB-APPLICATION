{{-- Honeypot, signed start time and (when configured) a CAPTCHA challenge. --}}
@php
    $guard = app(\App\Services\Spam\SpamGuard::class);
    $captcha = $guard->captcha();
@endphp
<div class="honeypot" aria-hidden="true">
    <label for="{{ \App\Services\Spam\SpamGuard::HONEYPOT_FIELD }}-{{ $attributes->get('id', 'form') }}">Leave this field empty</label>
    <input type="text" id="{{ \App\Services\Spam\SpamGuard::HONEYPOT_FIELD }}-{{ $attributes->get('id', 'form') }}" name="{{ \App\Services\Spam\SpamGuard::HONEYPOT_FIELD }}" value="" tabindex="-1" autocomplete="off">
</div>
<input type="hidden" name="{{ \App\Services\Spam\SpamGuard::TIMESTAMP_FIELD }}" value="{{ $guard->timestampToken() }}">
@if ($captcha->enabled() && $captcha instanceof \App\Services\Spam\TurnstileCaptchaVerifier)
    <div class="cf-turnstile" data-turnstile data-sitekey="{{ $captcha->siteKey() }}" data-theme="dark"></div>
    @error('captcha')
        <p class="field-error">{{ $message }}</p>
    @enderror
@endif
