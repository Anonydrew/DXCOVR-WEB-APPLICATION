@extends('layouts.admin')

@section('title', 'Navigation')

@section('content')
    <x-admin.page-header title="Navigation" description="Menus in the header and footer. Links to custom pages are hidden automatically while those pages are unpublished." />

    <div class="grid gap-4 md:grid-cols-2">
        @foreach ($menus as $menu)
            <a href="{{ route('admin.navigation.edit', $menu) }}" class="a-card group flex items-center gap-4 p-5 hover:border-line-strong">
                <span class="grid size-11 place-items-center rounded-xl bg-surface-2 text-lime"><x-icon name="navigation" :size="20" /></span>
                <span class="flex-1">
                    <span class="block font-semibold group-hover:text-lime">{{ $menu->name }}</span>
                    <span class="block text-xs a-subtle">{{ $menu->items_count }} items</span>
                </span>
                <x-icon name="chevron-right" :size="16" class="a-subtle" />
            </a>
        @endforeach
    </div>
@endsection
