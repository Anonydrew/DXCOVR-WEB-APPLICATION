@extends('layouts.admin')

@section('title', 'Testimonials')

@section('content')
    <x-admin.page-header title="Testimonials" description="Only publish genuine testimonials from real clients who have agreed to be quoted.">
        <x-slot:actions>
            @if ($canManage)
                <a href="{{ route('admin.testimonials.create') }}" class="a-btn a-btn-primary"><x-icon name="plus" :size="16" /> Add testimonial</a>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    @if ($testimonials->isEmpty())
        <x-admin.empty-state title="No testimonials" icon="quote">The testimonials section stays hidden on the website until a consented testimonial is published.</x-admin.empty-state>
    @else
        <div class="a-card overflow-hidden">
            <ul class="divide-y divide-line" @if ($canManage) x-data="sortableList(@js(route('admin.reorder', 'testimonials')))" @endif>
                @foreach ($testimonials as $testimonial)
                    <li class="flex items-start gap-4 px-4 py-4" data-id="{{ $testimonial->id }}">
                        @if ($canManage)
                            <span class="a-drag-handle a-icon-btn" aria-hidden="true"><x-icon name="drag" :size="16" /></span>
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="line-clamp-2">“{{ $testimonial->quote }}”</p>
                            <p class="mt-1 text-xs a-subtle">{{ $testimonial->author_name }}{{ $testimonial->organisation ? ', '.$testimonial->organisation : '' }}{{ $testimonial->project ? ' · '.$testimonial->project->title : '' }}</p>
                        </div>
                        @if (! $testimonial->consent_confirmed)
                            <span class="a-badge a-badge-warning">No consent recorded</span>
                        @endif
                        <span class="a-badge {{ $testimonial->is_published && $testimonial->consent_confirmed ? 'a-badge-success' : 'a-badge-muted' }}">{{ $testimonial->is_published && $testimonial->consent_confirmed ? 'Published' : 'Hidden' }}</span>
                        @if ($canManage)
                            <a href="{{ route('admin.testimonials.edit', $testimonial) }}" class="a-icon-btn" aria-label="Edit testimonial from {{ $testimonial->author_name }}"><x-icon name="edit" :size="16" /></a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
@endsection
