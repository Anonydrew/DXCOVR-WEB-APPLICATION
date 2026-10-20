@props(['services' => collect(), 'type' => 'project', 'product' => null, 'compact' => false, 'id' => 'enquiry', 'selectedService' => null])
@php
    $site = app(\App\Services\SiteSettings::class);
    $isProductForm = $product !== null;
    $selectedType = old('type', $type);
@endphp
<form method="POST" action="{{ route('contact.store') }}" class="grid gap-6" data-submit-form novalidate {{ $attributes }}>
    @csrf
    <x-site.spam-fields :id="$id" />
    <input type="hidden" name="source_url" value="{{ url()->current() }}">

    <x-site.form-errors />

    @if ($isProductForm)
        <input type="hidden" name="product_id" value="{{ $product->id }}">
        <fieldset>
            <legend class="field-label">What would you like?</legend>
            <div class="flex flex-wrap gap-3">
                @if ($product->demo_requests_enabled)
                    <label class="chip cursor-pointer has-[:checked]:border-lime has-[:checked]:text-text">
                        <input type="radio" name="type" value="demo" class="accent-lime" @checked($selectedType === 'demo' || ! $product->enquiries_enabled)> Request a demo
                    </label>
                @endif
                @if ($product->enquiries_enabled)
                    <label class="chip cursor-pointer has-[:checked]:border-lime has-[:checked]:text-text">
                        <input type="radio" name="type" value="product" class="accent-lime" @checked($selectedType === 'product' || ! $product->demo_requests_enabled)> Ask a question
                    </label>
                @endif
            </div>
        </fieldset>
    @elseif (! $compact)
        <fieldset>
            <legend class="field-label">I would like to</legend>
            <div class="flex flex-wrap gap-3">
                <label class="chip cursor-pointer has-[:checked]:border-lime has-[:checked]:text-text">
                    <input type="radio" name="type" value="project" class="accent-lime" @checked($selectedType !== 'contact')> Start a project
                </label>
                <label class="chip cursor-pointer has-[:checked]:border-lime has-[:checked]:text-text">
                    <input type="radio" name="type" value="contact" class="accent-lime" @checked($selectedType === 'contact')> Ask a general question
                </label>
            </div>
        </fieldset>
    @else
        <input type="hidden" name="type" value="project">
    @endif

    <div class="grid gap-6 sm:grid-cols-2">
        <x-site.input name="name" label="Your name" required autocomplete="name" maxlength="120" :id="$id.'-name'" />
        <x-site.input name="email" type="email" label="Email address" required autocomplete="email" maxlength="190" :id="$id.'-email'" />
        <x-site.input name="organisation" label="Organisation" autocomplete="organization" maxlength="160" :id="$id.'-organisation'" />
        <x-site.input name="phone" type="tel" label="Phone" autocomplete="tel" maxlength="40" :id="$id.'-phone'" />
    </div>

    @unless ($isProductForm)
        <div class="grid gap-6 sm:grid-cols-2">
            <x-site.select name="service_id" label="Service of interest" :options="$services->pluck('title', 'id')" :value="$selectedService" placeholder="Choose a service" :id="$id.'-service'" />
            <x-site.select name="project_type" label="Project type" :options="\App\Models\Enquiry::PROJECT_TYPES" placeholder="Choose a type" :id="$id.'-project-type'" />
            @unless ($compact)
                <x-site.select name="budget_range" label="Budget range" :options="\App\Models\Enquiry::BUDGET_RANGES" placeholder="Choose a range" :id="$id.'-budget'" hint="Helps us suggest the right scope. Indicative only." />
                <x-site.select name="timeframe" label="Expected timeframe" :options="\App\Models\Enquiry::TIMEFRAMES" placeholder="Choose a timeframe" :id="$id.'-timeframe'" />
            @endunless
        </div>
    @endunless

    <x-site.textarea name="message" :label="$isProductForm ? 'How can we help?' : 'Tell us about the project'" required maxlength="5000" :rows="$compact ? 5 : 7" :id="$id.'-message'"
        :hint="$isProductForm ? null : 'What problem are you solving, who is it for, and what does success look like?'" />

    <x-site.consent :notice="$site->get('privacy.enquiry_notice')" :id="$id.'-consent'" />

    <div class="flex flex-wrap items-center gap-4">
        <button type="submit" class="btn btn-primary" data-loading-text="Sending…">
            <span data-label>{{ $isProductForm ? 'Send request' : 'Send enquiry' }}</span>
            <x-icon name="arrow-right" :size="18" class="btn-arrow" />
        </button>
        <p class="text-sm text-subtle">We usually reply within two working days.</p>
    </div>
</form>
