<section class="surface-dark section" aria-labelledby="team-{{ $section->id }}">
    <div class="container-dx">
        <x-site.section-heading :id="'team-'.$section->id" :eyebrow="$section->value('eyebrow')" :heading="$section->value('heading')" :intro="$section->value('intro')" />
        <ul class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($data['members'] as $member)
                <li class="card p-3" data-reveal style="--reveal-delay:{{ min($loop->index * 0.05, 0.3) }}s">
                    <div class="media-frame aspect-square rounded-[calc(var(--radius-card)-0.5rem)]">
                        @if ($member->photo)
                            <x-site.picture :media="$member->photo" sizes="(min-width: 1024px) 25vw, 50vw" class="h-full w-full object-cover" />
                        @else
                            <div class="grid h-full place-items-center"><span class="font-display text-6xl font-semibold text-line-strong" aria-hidden="true">{{ $member->initials() }}</span></div>
                        @endif
                    </div>
                    <div class="p-4">
                        <h3 class="heading-md">{{ $member->name }}</h3>
                        @if ($member->role_title)
                            <p class="mt-1 text-sm text-muted">{{ $member->role_title }}</p>
                        @endif
                        @if ($member->short_bio)
                            <p class="mt-3 text-sm text-subtle">{{ $member->short_bio }}</p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</section>
