@php
    $projects = $data['projects'];

    /*
     * Tile widths on a 24-column grid, per number of projects. Each row adds
     * up to 24, alternating wide and narrow tiles like an editorial gallery.
     */
    $layouts = [
        1 => [24],
        2 => [14, 10],
        3 => [10, 7, 7],
        4 => [7, 7, 5, 5],
        5 => [7, 7, 5, 5, 24],
        6 => [7, 7, 5, 5, 12, 12],
        7 => [7, 7, 5, 5, 7, 5, 12],
    ];
    $spans = $layouts[min(max($projects->count(), 1), 7)];
    $headingId = 'work-'.$section->id;
@endphp
<section class="surface-light work-gallery" aria-labelledby="{{ $headingId }}">
    <div class="work-gallery-inner">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <h2 id="{{ $headingId }}" class="work-gallery-heading" data-reveal>{{ $section->value('heading') ?: 'Selected work' }}</h2>
            @if ($section->value('cta_label') && $section->value('cta_url'))
                <a href="{{ $section->value('cta_url') }}" class="link-arrow" data-reveal>
                    {{ $section->value('cta_label') }}
                    <x-icon name="arrow-right" :size="18" class="btn-arrow accent" />
                </a>
            @endif
        </div>

        <ul class="work-gallery-grid mt-10 sm:mt-14">
            @foreach ($projects as $project)
                @php
                    $span = $spans[$loop->index] ?? 12;
                    $shape = match (true) {
                        $span >= 20 => 'work-tile-panorama',
                        $span >= 12 => 'work-tile-wide',
                        $span >= 7 => 'work-tile-tall',
                        default => 'work-tile-compact',
                    };
                @endphp
                <li class="work-tile {{ $shape }}" style="--span: {{ $span }}; --reveal-delay: {{ min($loop->index * 0.05, 0.3) }}s" data-reveal>
                    <a href="{{ route('work.show', $project) }}" class="group block">
                        <div class="work-tile-media">
                            @if ($project->cover)
                                <x-site.picture :media="$project->cover" :sizes="$span >= 12 ? '(min-width: 1024px) 50vw, 100vw' : '(min-width: 1024px) 30vw, 100vw'" class="h-full w-full object-cover" />
                            @else
                                <x-site.cover-art :seed="$project->slug" :glyph="$project->services->first()?->icon ?? 'software'" :label="$project->title" />
                            @endif
                        </div>
                        <h3 class="work-tile-title">{{ $project->title }}</h3>
                        <p class="work-tile-line">{{ $project->shortLine() }}</p>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</section>
