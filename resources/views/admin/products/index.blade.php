@extends('layouts.admin')

@section('title', 'Products')

@section('content')
    <x-admin.page-header title="Products" description="DXCOVR’s own software products. Drag to change the order.">
        <x-slot:actions>
            @if ($canManage)
                <a href="{{ route('admin.products.create') }}" class="a-btn a-btn-primary"><x-icon name="plus" :size="16" /> New product</a>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    @if ($products->isEmpty())
        <x-admin.empty-state title="No products yet" icon="box">Add a product to give it its own page with features, screenshots and a demo request form.</x-admin.empty-state>
    @else
        <div class="a-card overflow-hidden">
            <ul class="divide-y divide-line" @if ($canManage) x-data="sortableList(@js(route('admin.reorder', 'products')))" @endif>
                @foreach ($products as $product)
                    <li class="flex items-center gap-4 px-4 py-3" data-id="{{ $product->id }}">
                        @if ($canManage)
                            <span class="a-drag-handle a-icon-btn" aria-hidden="true"><x-icon name="drag" :size="16" /></span>
                        @endif
                        <div class="a-thumb a-checker size-12 flex-none !rounded-lg">
                            @if ($product->logo)
                                <img src="{{ $product->logo->variantUrl(480) }}" alt="" class="!object-contain p-1">
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            @if ($canManage)
                                <a href="{{ route('admin.products.edit', $product) }}" class="font-semibold hover:text-lime">{{ $product->name }}</a>
                            @else
                                <span class="font-semibold">{{ $product->name }}</span>
                            @endif
                            <p class="truncate text-xs a-subtle">{{ $product->product_status->label() }} · {{ $product->projects_count }} linked case studies</p>
                        </div>
                        @if ($product->is_featured)
                            <span class="a-badge a-badge-info">Featured</span>
                        @endif
                        <x-admin.status-badge :status="$product->status" />
                        @if ($canManage)
                            <a href="{{ route('admin.products.edit', $product) }}" class="a-icon-btn" aria-label="Edit {{ $product->name }}"><x-icon name="edit" :size="16" /></a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
@endsection
