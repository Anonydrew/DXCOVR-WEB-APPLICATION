@php
    $library = $data['library'] ?? collect();
    $banner = $data['media'] ?? null;
    $columns = collect($section->value('columns', []))->filter(fn ($column) => filled($column['body'] ?? null));
    $photos = collect([$section->value('photo_one_media_id'), $section->value('photo_two_media_id')])
        ->filter()
        ->map(fn ($id) => $library->get((int) $id))
        ->filter();
    $headingId = 'mission-'.$section->id;
@endphp
@if ($banner)
    <div class="mission-banner" aria-hidden="true">
        <x-site.picture :media="$banner" sizes="100vw" alt="" class="h-full w-full object-cover" />
    </div>
@endif
<section class="mission-band" aria-labelledby="{{ $headingId }}">
    <div class="container-dx">
        <h2 id="{{ $headingId }}" class="mission-heading" data-reveal>{{ $section->value('heading') ?: 'Our mission' }}</h2>
        <div class="mt-6 grid gap-8 md:grid-cols-3 md:gap-10">
            @foreach ($columns as $column)
                <div data-reveal style="--reveal-delay:{{ $loop->index * 0.05 }}s">
                    <p class="text-lg font-semibold leading-relaxed">{{ $column['body'] }}</p>
                    @if (! empty($column['link_label']) && ! empty($column['link_url']))
                        <a href="{{ $column['link_url'] }}" class="mt-4 inline-flex items-center gap-1.5 font-semibold text-lime transition-opacity hover:opacity-80">
                            {{ $column['link_label'] }} <x-icon name="arrow-right" :size="16" />
                        </a>
                    @endif
                </div>
            @endforeach
        </div>

        @if ($photos->isNotEmpty())
            <div class="mt-14 grid gap-4 {{ $photos->count() > 1 ? 'md:grid-cols-2' : '' }}">
                @foreach ($photos as $photo)
                    <div class="overflow-hidden rounded-[1.25rem] {{ $photos->count() > 1 ? 'aspect-[4/3]' : 'aspect-[21/9]' }}" data-reveal>
                        <x-site.picture :media="$photo" :sizes="$photos->count() > 1 ? '(min-width: 768px) 50vw, 100vw' : '100vw'" class="h-full w-full object-cover" />
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
