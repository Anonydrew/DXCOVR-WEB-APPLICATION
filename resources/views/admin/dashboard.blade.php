@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <x-admin.page-header :title="'Welcome, '.\Illuminate\Support\Str::before(auth()->user()->name, ' ')" description="Here is what needs your attention across the DXCOVR website." />

    @if ($needsTwoFactor)
        <div class="a-alert a-alert-info mb-6">
            <x-icon name="shield" :size="18" class="mt-0.5 flex-none text-info" />
            <div class="flex-1">Protect your account with two-factor authentication.</div>
            <a href="{{ route('admin.account') }}#security" class="a-btn a-btn-sm">Set up</a>
        </div>
    @endif

    @if ($stats !== [])
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($stats as $stat)
                <a href="{{ $stat['route'] }}" class="a-card group p-5 transition hover:border-line-strong">
                    <p class="a-mono a-subtle">{{ $stat['label'] }}</p>
                    <p class="mt-3 font-display text-4xl font-semibold group-hover:text-lime">{{ number_format($stat['value']) }}</p>
                    @isset($stat['hint'])
                        <p class="mt-1 text-xs a-muted">{{ $stat['hint'] }}</p>
                    @endisset
                </a>
            @endforeach
        </div>
    @endif

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <section class="a-card xl:col-span-2" aria-labelledby="attention-heading">
            <div class="a-card-header"><h2 id="attention-heading" class="a-card-title">Needs attention</h2></div>
            @if ($attention === [])
                <p class="a-card-body text-sm a-muted">Nothing needs attention right now.</p>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($attention as $item)
                        <li>
                            <a href="{{ $item['route'] }}" class="flex items-center gap-3 px-5 py-3.5 text-sm hover:bg-white/[0.02]">
                                <span class="size-2 flex-none rounded-full {{ $item['tone'] === 'warning' ? 'bg-warning' : 'bg-info' }}" aria-hidden="true"></span>
                                <span class="flex-1">{{ $item['text'] }}</span>
                                <x-icon name="chevron-right" :size="16" class="a-subtle" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="a-card" aria-labelledby="quick-heading">
            <div class="a-card-header"><h2 id="quick-heading" class="a-card-title">Quick actions</h2></div>
            <div class="a-card-body grid gap-2">
                @can('create', \App\Models\Article::class)
                    <a href="{{ route('admin.articles.create') }}" class="a-btn justify-start"><x-icon name="file" :size="16" /> Write an article</a>
                @endcan
                @can('projects.manage')
                    <a href="{{ route('admin.projects.create') }}" class="a-btn justify-start"><x-icon name="briefcase" :size="16" /> Add a case study</a>
                @endcan
                @can('careers.manage')
                    <a href="{{ route('admin.jobs.create') }}" class="a-btn justify-start"><x-icon name="users" :size="16" /> Post a job</a>
                @endcan
                @can('media.upload')
                    <a href="{{ route('admin.media.index') }}" class="a-btn justify-start"><x-icon name="upload" :size="16" /> Upload media</a>
                @endcan
                @can('pages.manage')
                    <a href="{{ route('admin.pages.index') }}" class="a-btn justify-start"><x-icon name="layers" :size="16" /> Edit the homepage</a>
                @endcan
            </div>
        </section>

        @if ($myDrafts->isNotEmpty())
            <section class="a-card" aria-labelledby="drafts-heading">
                <div class="a-card-header"><h2 id="drafts-heading" class="a-card-title">Your drafts</h2></div>
                <ul class="divide-y divide-line text-sm">
                    @foreach ($myDrafts as $draft)
                        <li class="flex items-center justify-between gap-3 px-5 py-3">
                            <a href="{{ route('admin.articles.edit', $draft) }}" class="truncate font-medium hover:text-lime">{{ $draft->title }}</a>
                            <x-admin.status-badge :status="$draft->status" />
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($scheduled->isNotEmpty())
            <section class="a-card" aria-labelledby="scheduled-heading">
                <div class="a-card-header"><h2 id="scheduled-heading" class="a-card-title">Scheduled</h2></div>
                <ul class="divide-y divide-line text-sm">
                    @foreach ($scheduled as $item)
                        <li class="flex items-center justify-between gap-3 px-5 py-3">
                            <a href="{{ route('admin.articles.edit', $item) }}" class="truncate font-medium hover:text-lime">{{ $item->title }}</a>
                            <span class="whitespace-nowrap text-xs text-info">{{ $item->published_at->format('j M, H:i') }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @can('audit.view')
            <section class="a-card {{ $myDrafts->isEmpty() && $scheduled->isEmpty() ? 'xl:col-span-3' : '' }}" aria-labelledby="activity-heading">
                <div class="a-card-header">
                    <h2 id="activity-heading" class="a-card-title">Recent activity</h2>
                    <a href="{{ route('admin.audit.index') }}" class="text-sm a-muted hover:text-text">View all</a>
                </div>
                @if ($activity->isEmpty())
                    <p class="a-card-body text-sm a-muted">No activity recorded yet.</p>
                @else
                    <ul class="divide-y divide-line text-sm">
                        @foreach ($activity as $entry)
                            <li class="flex items-start gap-3 px-5 py-3">
                                <span class="grid size-7 flex-none place-items-center rounded-full bg-surface-3 text-[0.65rem] font-bold">{{ $entry->user?->initials() ?? 'Not set' }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate">{{ $entry->description ?? $entry->action }}</p>
                                    <p class="text-xs a-subtle">{{ $entry->user?->name ?? 'System' }} · {{ $entry->created_at->diffForHumans() }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endcan
    </div>
@endsection
