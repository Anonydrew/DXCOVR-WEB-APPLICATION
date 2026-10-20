@php($services = $data['services']->take((int) ($section->value('limit') ?: 7)))
<section class="surface-dark section relative" aria-labelledby="capabilities-{{ $section->id }}">
    <div class="container-dx">
        <x-site.section-heading
            :id="'capabilities-'.$section->id"
            :eyebrow="$section->value('eyebrow')"
            :heading="$section->value('heading')"
            :intro="$section->value('intro')"
            :cta-label="$section->value('cta_label')"
            :cta-url="$section->value('cta_url')"
        />

        <ol class="mt-14 sm:mt-20">
            @foreach ($services as $service)
                <li class="index-row group" data-reveal style="--reveal-delay:{{ min($loop->index * 0.04, 0.3) }}s">
                    <div class="grid items-center gap-4 py-7 sm:grid-cols-12 sm:gap-6 sm:py-9">
                        <span class="mono-label text-subtle sm:col-span-1">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        <h3 class="index-title heading-lg sm:col-span-5">
                            <a href="{{ route('services.show', $service) }}" class="stretched-link">{{ $service->title }}</a>
                        </h3>
                        <p class="text-muted sm:col-span-5">{{ $service->short_description }}</p>
                        <div class="hidden items-center justify-end gap-4 text-muted sm:col-span-1 sm:flex" aria-hidden="true">
                            <x-icon name="arrow-up-right" :size="24" class="transition duration-500 group-hover:rotate-45 group-hover:text-lime" />
                        </div>
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
</section>
