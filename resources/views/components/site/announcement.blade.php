@props(['announcement'])
<div data-announcement="{{ $announcement->id }}-{{ $announcement->updated_at?->timestamp }}" class="border-b border-line bg-surface text-sm" role="region" aria-label="Announcement">
    <div class="container-dx flex items-center gap-4 py-2.5">
        <span class="badge badge-lime hidden sm:inline-flex">News</span>
        <p class="min-w-0 flex-1 text-muted">
            <strong class="font-semibold text-text">{{ $announcement->title }}</strong>
            @if ($announcement->body)
                <span class="hidden md:inline">. {{ $announcement->body }}</span>
            @endif
            @if ($announcement->link_url)
                <a href="{{ $announcement->link_url }}" class="link-underline ml-1 font-semibold text-text">{{ $announcement->link_label ?: 'Learn more' }}</a>
            @endif
        </p>
        <button type="button" data-announcement-dismiss class="grid size-8 flex-none place-items-center rounded-full text-muted hover:bg-white/5 hover:text-text" aria-label="Dismiss announcement">
            <x-icon name="close" :size="16" />
        </button>
    </div>
</div>
