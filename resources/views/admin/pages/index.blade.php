@extends('layouts.admin')

@section('title', 'Pages')

@section('content')
    <x-admin.page-header title="Pages & homepage" description="Edit the homepage and core pages through predefined sections, and manage custom and legal pages.">
        <x-slot:actions>
            <a href="{{ route('admin.pages.create') }}" class="a-btn a-btn-primary"><x-icon name="plus" :size="16" /> New page</a>
        </x-slot:actions>
    </x-admin.page-header>

    <section class="a-card mb-6 overflow-hidden" aria-labelledby="system-pages">
        <div class="a-card-header">
            <h2 id="system-pages" class="a-card-title">Core pages</h2>
            <span class="text-xs a-subtle">Always live. Edit their sections and search metadata.</span>
        </div>
        <ul class="divide-y divide-line">
            @foreach ($systemPages as $page)
                <li class="flex items-center gap-4 px-4 py-3">
                    <x-icon :name="$page->system_key === 'home' ? 'grid' : 'file'" :size="18" class="a-subtle" />
                    <div class="min-w-0 flex-1">
                        <a href="{{ route('admin.pages.edit', $page) }}" class="font-semibold hover:text-lime">{{ $page->system_key === 'home' ? 'Homepage' : $page->title }}</a>
                        <p class="text-xs a-subtle">{{ parse_url($page->publicUrl(), PHP_URL_PATH) ?: '/' }} · {{ $page->sections_count }} sections</p>
                    </div>
                    <a href="{{ $page->publicUrl() }}" target="_blank" rel="noopener" class="a-icon-btn" aria-label="View {{ $page->title }}"><x-icon name="external" :size="16" /></a>
                    <a href="{{ route('admin.pages.edit', $page) }}" class="a-icon-btn" aria-label="Edit {{ $page->title }}"><x-icon name="edit" :size="16" /></a>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="a-card overflow-hidden" aria-labelledby="custom-pages">
        <div class="a-card-header"><h2 id="custom-pages" class="a-card-title">Custom & legal pages</h2></div>
        @if ($pages->isEmpty())
            <p class="a-card-body text-sm a-muted">No custom pages yet.</p>
        @else
            <ul class="divide-y divide-line">
                @foreach ($pages as $page)
                    <li class="flex items-center gap-4 px-4 py-3">
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('admin.pages.edit', $page) }}" class="font-semibold hover:text-lime">{{ $page->title }}</a>
                            <p class="text-xs a-subtle">/{{ $page->slug }} · {{ \App\Models\Page::TEMPLATES[$page->template] ?? $page->template }}</p>
                        </div>
                        <x-admin.status-badge :status="$page->status" />
                        <a href="{{ route('admin.pages.edit', $page) }}" class="a-icon-btn" aria-label="Edit {{ $page->title }}"><x-icon name="edit" :size="16" /></a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endsection
