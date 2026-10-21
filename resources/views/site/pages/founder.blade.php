@extends('layouts.site')

@section('content')
    <section class="surface-dark relative overflow-hidden">
        <div class="container-dx relative pt-12 pb-16 sm:pt-16 sm:pb-24">
            <x-site.breadcrumbs :items="$seo->breadcrumbItems()" />
            <div class="mt-10 grid items-end gap-12 lg:grid-cols-12">
                <div class="lg:col-span-5">
                    <div class="media-frame aspect-[4/5] rounded-[var(--radius-card)] border border-line">
                        @if ($founder->photo)
                            <x-site.picture :media="$founder->photo" :eager="true" sizes="(min-width: 1024px) 40vw, 100vw" class="h-full w-full object-cover" />
                        @else
                            <div class="grid h-full place-items-center"><span class="font-display text-[9rem] font-semibold text-line-strong" aria-hidden="true">{{ $founder->initials() }}</span></div>
                        @endif
                    </div>
                </div>
                <div class="lg:col-span-7 lg:pl-6">
                    <p class="eyebrow accent">Founder</p>
                    <h1 class="display-lg mt-5">{{ $founder->name }}</h1>
                    @if ($founder->role_title)
                        <p class="heading-md mt-4 font-medium text-muted">{{ $founder->role_title }}</p>
                    @endif
                    @if ($founder->short_bio)
                        <p class="lede text-muted mt-6 max-w-2xl">{{ $founder->short_bio }}</p>
                    @endif
                    @if (! empty($founder->links))
                        <ul class="mt-8 flex flex-wrap gap-2">
                            @foreach ($founder->links as $link)
                                <li><a href="{{ $link['url'] }}" class="chip" target="_blank" rel="noopener me">{{ $link['label'] }} <x-icon name="arrow-up-right" :size="14" /></a></li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="surface-light section">
        <div class="container-dx grid gap-14 lg:grid-cols-12">
            <div class="lg:col-span-7">
                @if (trim(strip_tags($bio)) !== '')
                    <h2 class="eyebrow accent">Biography</h2>
                    <div class="prose-dx mt-6">{!! $bio !!}</div>
                @endif

                @if (! empty($founder->experience))
                    <h2 class="heading-lg mt-16">Experience</h2>
                    <ol class="mt-8 space-y-8 border-l border-paper-line pl-8">
                        @foreach ($founder->experience as $role)
                            <li class="relative">
                                <span class="absolute -left-[2.35rem] top-1.5 size-3 rounded-full border-2 border-lime-deep bg-paper" aria-hidden="true"></span>
                                @if (! empty($role['period']))
                                    <p class="mono-label accent">{{ $role['period'] }}</p>
                                @endif
                                <h3 class="heading-md mt-1">{{ $role['role'] }}@if (! empty($role['organisation']))<span class="text-paper-muted"> · {{ $role['organisation'] }}</span>@endif</h3>
                                @if (! empty($role['summary']))
                                    <p class="mt-2 text-paper-muted">{{ $role['summary'] }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>

            <aside class="space-y-10 lg:col-span-4 lg:col-start-9">
                @if (! empty($founder->expertise))
                    <div>
                        <h2 class="mono-label text-paper-muted">Areas of expertise</h2>
                        <ul class="mt-4 space-y-2 font-display text-lg">
                            @foreach ($founder->expertise as $area)
                                <li class="flex gap-3"><span class="mt-2.5 size-1.5 flex-none rounded-full bg-lime-deep" aria-hidden="true"></span>{{ $area }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if (! empty($founder->skills))
                    <div>
                        <h2 class="mono-label text-paper-muted">Skills</h2>
                        <ul class="mt-4 flex flex-wrap gap-2">
                            @foreach ($founder->skills as $skill)
                                <li class="chip">{{ $skill }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="card p-6">
                    <p class="heading-md">Have a project in mind?</p>
                    <p class="mt-2 text-sm text-paper-muted">Tell us what you are trying to solve and we will help you shape it.</p>
                    <a href="{{ route('contact') }}" class="btn btn-dark mt-5">Start a project <x-icon name="arrow-right" :size="16" class="btn-arrow" /></a>
                </div>
            </aside>
        </div>
    </section>

    @if ($founder->projects->isNotEmpty())
        <section class="surface-raised section">
            <div class="container-dx">
                <x-site.section-heading eyebrow="Selected projects" heading="Work led and shaped" />
                <div class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($founder->projects as $project)
                        <x-site.project-card :project="$project" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($articles->isNotEmpty())
        <section class="surface-light section">
            <div class="container-dx">
                <x-site.section-heading eyebrow="Writing" heading="Articles and insights" />
                <div class="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($articles as $article)
                        <x-site.article-card :article="$article" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
