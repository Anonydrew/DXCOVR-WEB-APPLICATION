@extends('layouts.admin')

@section('title', 'Services')

@section('content')
    <x-admin.page-header title="Services" description="The service pillars shown on the website. Drag to change the order.">
        <x-slot:actions>
            @if ($canManage)
                <a href="{{ route('admin.services.create') }}" class="a-btn a-btn-primary"><x-icon name="plus" :size="16" /> New service</a>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    @if ($services->isEmpty())
        <x-admin.empty-state title="No services yet" icon="layers">Create the first service pillar to describe what DXCOVR offers.</x-admin.empty-state>
    @else
        <div class="a-card overflow-hidden">
            <ul class="divide-y divide-line" @if ($canManage) x-data="sortableList(@js(route('admin.reorder', 'services')))" @endif>
                @foreach ($services as $service)
                    <li class="flex items-center gap-4 px-4 py-3" data-id="{{ $service->id }}">
                        @if ($canManage)
                            <span class="a-drag-handle a-icon-btn" aria-hidden="true" title="Drag to reorder"><x-icon name="drag" :size="16" /></span>
                        @endif
                        <x-site.glyph :name="$service->icon" :size="36" class="flex-none text-muted" />
                        <div class="min-w-0 flex-1">
                            @if ($canManage)
                                <a href="{{ route('admin.services.edit', $service) }}" class="font-semibold hover:text-lime">{{ $service->title }}</a>
                            @else
                                <span class="font-semibold">{{ $service->title }}</span>
                            @endif
                            <p class="truncate text-xs a-subtle">{{ $service->short_description }}</p>
                        </div>
                        <span class="hidden text-xs a-subtle md:block">{{ $service->projects_count }} projects · {{ $service->products_count }} products</span>
                        @if ($service->is_featured)
                            <span class="a-badge a-badge-info">Featured</span>
                        @endif
                        <x-admin.status-badge :status="$service->status" />
                        @if ($canManage)
                            <a href="{{ route('admin.services.edit', $service) }}" class="a-icon-btn" aria-label="Edit {{ $service->title }}"><x-icon name="edit" :size="16" /></a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
@endsection
