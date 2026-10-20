@extends('layouts.admin')

@section('title', $plural)

@section('content')
    <x-admin.page-header :title="$plural" :description="$description" />

    @if ($tabs)
        @include('admin.partials.tabs', ['tabs' => $tabs])
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="a-card overflow-hidden">
            @if ($items->isEmpty())
                <p class="p-6 text-sm a-muted">Nothing here yet. Add the first {{ $singular }} using the form.</p>
            @else
                <ul class="divide-y divide-line" @if ($sortable) x-data="sortableList(@js(route('admin.reorder', $sortable)))" @endif>
                    @foreach ($items as $item)
                        <li data-id="{{ $item->id }}" x-data="{ editing: {{ $errors->any() && (string) old('_item') === (string) $item->id ? 'true' : 'false' }} }" class="px-4 py-3">
                            <div class="flex items-center gap-3" x-show="! editing">
                                @if ($sortable)
                                    <span class="a-drag-handle a-icon-btn" aria-hidden="true"><x-icon name="drag" :size="16" /></span>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <p class="font-semibold">{{ $item->name }}</p>
                                    <p class="truncate text-xs a-subtle">{{ $item->slug }}@if ($hasDescription && $item->description) · {{ $item->description }}@endif</p>
                                </div>
                                @if ($usageRelation)
                                    <span class="text-xs a-subtle">{{ $item->{\Illuminate\Support\Str::snake($usageRelation).'_count'} }} in use</span>
                                @endif
                                <button type="button" class="a-icon-btn" @click="editing = true" aria-label="Edit {{ $item->name }}"><x-icon name="edit" :size="16" /></button>
                                <x-admin.delete-button :action="route($routePrefix.'.destroy', $item->id)" :confirm="'Delete “'.$item->name.'”? It will be removed from anything that uses it.'" label="" class="!px-2" :aria-label="'Delete '.$item->name" />
                            </div>
                            <form method="POST" action="{{ route($routePrefix.'.update', $item->id) }}" x-show="editing" x-cloak class="grid gap-3 sm:grid-cols-2">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="_item" value="{{ $item->id }}">
                                <x-admin.input name="name" label="Name" :value="$item->name" required :id="'name-'.$item->id" />
                                <x-admin.input name="slug" label="Slug" :value="$item->slug" :id="'slug-'.$item->id" />
                                @if ($hasDescription)
                                    <x-admin.textarea name="description" label="Description" :value="$item->description" rows="2" class="sm:col-span-2" :id="'description-'.$item->id" />
                                @endif
                                <div class="flex gap-2 sm:col-span-2">
                                    <button type="submit" class="a-btn a-btn-primary a-btn-sm">Save</button>
                                    <button type="button" class="a-btn a-btn-sm a-btn-ghost" @click="editing = false">Cancel</button>
                                </div>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <form method="POST" action="{{ route($routePrefix.'.store') }}" class="a-card a-card-body h-fit space-y-4">
            @csrf
            <h2 class="a-card-title">Add {{ $singular }}</h2>
            @php($isNew = old('_item') === null)
            <x-admin.input name="name" label="Name" required :value="$isNew ? old('name') : null" id="new-name" />
            <x-admin.input name="slug" label="Slug" hint="Optional. Generated from the name." :value="$isNew ? old('slug') : null" id="new-slug" />
            @if ($hasDescription)
                <x-admin.textarea name="description" label="Description" rows="2" :value="$isNew ? old('description') : null" id="new-description" />
            @endif
            <button type="submit" class="a-btn a-btn-primary w-full">Add {{ $singular }}</button>
        </form>
    </div>
@endsection
