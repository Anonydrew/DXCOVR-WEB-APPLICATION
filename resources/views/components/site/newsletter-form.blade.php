@props(['id' => 'newsletter', 'source' => null])
<form method="POST" action="{{ route('newsletter.subscribe') }}" class="grid gap-4" data-submit-form {{ $attributes }}>
    @csrf
    <x-site.spam-fields :id="$id" />
    <input type="hidden" name="source" value="{{ $source ?? request()->path() }}">
    <div class="flex flex-col gap-3 sm:flex-row">
        <div class="flex-1">
            <label for="{{ $id }}-email" class="sr-only">Email address</label>
            <input id="{{ $id }}-email" type="email" name="email" required autocomplete="email" maxlength="190" placeholder="you@organisation.com" class="field" value="{{ old('email') }}" @error('email', 'newsletter') aria-invalid="true" aria-describedby="{{ $id }}-error" @enderror>
        </div>
        <button type="submit" class="btn btn-primary" data-loading-text="Subscribing…"><span data-label>Subscribe</span></button>
    </div>
    <label class="flex items-start gap-3 text-sm text-muted">
        <input type="checkbox" name="consent" value="1" class="checkbox" required>
        <span>I agree to receive occasional emails from DXCOVR and understand I can unsubscribe at any time.</span>
    </label>
    @if ($errors->newsletter->any())
        <p id="{{ $id }}-error" class="field-error" role="alert">{{ $errors->newsletter->first() }}</p>
    @endif
    @if (session('newsletter_status'))
        <p class="alert alert-success text-sm" role="status">{{ session('newsletter_status') }}</p>
    @endif
</form>
