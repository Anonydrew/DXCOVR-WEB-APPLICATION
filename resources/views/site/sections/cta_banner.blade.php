<section class="bg-ink py-6 sm:py-10">
    <div class="container-dx">
        <div class="relative overflow-hidden rounded-[calc(var(--radius-card)+0.5rem)] bg-lime px-7 py-14 text-ink sm:px-14 sm:py-20" data-reveal>
            <svg class="pointer-events-none absolute -right-16 -top-16 size-80 opacity-25" viewBox="0 0 200 200" fill="none" aria-hidden="true"><circle cx="100" cy="100" r="90" stroke="#080A08" stroke-dasharray="3 8"/><circle cx="100" cy="100" r="60" stroke="#080A08"/><circle cx="100" cy="100" r="14" fill="#080A08"/></svg>
            <div class="relative grid gap-8 lg:grid-cols-12 lg:items-end">
                <div class="lg:col-span-8">
                    <h2 class="display-md">{{ $section->value('heading') }}</h2>
                    @if ($section->value('body'))
                        <p class="lede mt-5 max-w-2xl text-ink/80">{{ $section->value('body') }}</p>
                    @endif
                </div>
                @if ($section->value('cta_label') && $section->value('cta_url'))
                    <div class="lg:col-span-4 lg:flex lg:justify-end">
                        <a href="{{ $section->value('cta_url') }}" class="btn btn-dark focus-visible:outline-ink">
                            {{ $section->value('cta_label') }} <x-icon name="arrow-right" :size="18" class="btn-arrow" />
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
