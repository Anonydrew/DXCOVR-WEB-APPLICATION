@extends('layouts.admin')

@section('title', 'FAQs')

@section('content')
    <x-admin.page-header title="Frequently asked questions" description="FAQs can appear in page sections (by group) or on individual service and product pages.">
        <x-slot:actions>
            @if ($canManage)
                <a href="{{ route('admin.faqs.create') }}" class="a-btn a-btn-primary"><x-icon name="plus" :size="16" /> Add FAQ</a>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <nav class="mb-6 flex flex-wrap gap-2" aria-label="FAQ groups">
        <a href="{{ route('admin.faqs.index') }}" class="a-btn a-btn-sm {{ ! request('group') ? 'a-btn-primary' : '' }}">All</a>
        @foreach (\App\Models\Faq::GROUPS as $key => $label)
            <a href="{{ route('admin.faqs.index', ['group' => $key]) }}" class="a-btn a-btn-sm {{ request('group') === $key ? 'a-btn-primary' : '' }}">{{ $label }}</a>
        @endforeach
    </nav>

    @if ($faqs->isEmpty())
        <x-admin.empty-state title="No FAQs" icon="help">FAQ sections are hidden on the website until they contain published questions.</x-admin.empty-state>
    @else
        <div class="a-card overflow-hidden">
            <ul class="divide-y divide-line" @if ($canManage) x-data="sortableList(@js(route('admin.reorder', 'faqs')))" @endif>
                @foreach ($faqs as $faq)
                    <li class="flex items-center gap-4 px-4 py-3" data-id="{{ $faq->id }}">
                        @if ($canManage)
                            <span class="a-drag-handle a-icon-btn" aria-hidden="true"><x-icon name="drag" :size="16" /></span>
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold">{{ $faq->question }}</p>
                            <p class="truncate text-xs a-subtle">
                                {{ \App\Models\Faq::GROUPS[$faq->group] ?? $faq->group }}
                                @if ($faq->service) · Service: {{ $faq->service->title }} @endif
                                @if ($faq->product) · Product: {{ $faq->product->name }} @endif
                            </p>
                        </div>
                        <span class="a-badge {{ $faq->is_published ? 'a-badge-success' : 'a-badge-muted' }}">{{ $faq->is_published ? 'Published' : 'Hidden' }}</span>
                        @if ($canManage)
                            <a href="{{ route('admin.faqs.edit', $faq) }}" class="a-icon-btn" aria-label="Edit FAQ"><x-icon name="edit" :size="16" /></a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
@endsection
