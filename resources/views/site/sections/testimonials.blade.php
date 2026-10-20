<section class="surface-light section" aria-labelledby="testimonials-{{ $section->id }}">
    <div class="container-dx">
        <x-site.section-heading :id="'testimonials-'.$section->id" :eyebrow="$section->value('eyebrow')" :heading="$section->value('heading')" />
        <div class="mt-14 grid gap-6 lg:grid-cols-3">
            @foreach ($data['testimonials'] as $testimonial)
                <figure class="card flex flex-col p-8" data-reveal style="--reveal-delay:{{ $loop->index * 0.06 }}s">
                    <svg width="32" height="24" viewBox="0 0 32 24" class="accent" aria-hidden="true"><path fill="currentColor" d="M0 24V13.7C0 5.9 4.4 1.3 12.2 0l1.2 3.4C8.9 4.6 6.8 7.3 6.6 11.2H12V24zm18 0V13.7C18 5.9 22.4 1.3 30.2 0l1.2 3.4c-4.5 1.2-6.6 3.9-6.8 7.8H30V24z"/></svg>
                    <blockquote class="mt-6 flex-1 font-display text-xl leading-snug">{{ $testimonial->quote }}</blockquote>
                    <figcaption class="mt-8 border-t border-paper-line pt-5 text-sm">
                        <span class="block font-semibold">{{ $testimonial->author_name }}</span>
                        <span class="text-paper-muted">{{ collect([$testimonial->author_title, $testimonial->organisation])->filter()->implode(', ') }}</span>
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </div>
</section>
