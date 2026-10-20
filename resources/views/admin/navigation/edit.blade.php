@extends('layouts.admin')

@section('title', $menu->name)

@section('content')
    <x-admin.page-header :title="$menu->name" description="Drag items to reorder. Use paths such as /services for pages on this site." :back="route('admin.navigation.index')" />

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
        <div class="space-y-3">
            @if ($menu->rootItems->isEmpty())
                <x-admin.empty-state title="This menu is empty" icon="navigation">Add the first link using the form.</x-admin.empty-state>
            @else
                <ol class="space-y-3" x-data="sortableList(@js(route('admin.reorder', 'navigation')))">
                    @foreach ($menu->rootItems as $item)
                        <li data-id="{{ $item->id }}" class="a-card">
                            @include('admin.navigation.item', ['item' => $item, 'parents' => $parents, 'allowsChildren' => $allowsChildren])
                            @if ($item->children->isNotEmpty())
                                <ol class="space-y-2 border-t border-line p-3 pl-10" x-data="sortableList(@js(route('admin.reorder', 'navigation')))">
                                    @foreach ($item->children as $child)
                                        <li data-id="{{ $child->id }}" class="rounded-xl border border-line bg-ink-2">
                                            @include('admin.navigation.item', ['item' => $child, 'parents' => $parents, 'allowsChildren' => $allowsChildren])
                                        </li>
                                    @endforeach
                                </ol>
                            @endif
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>

        <form method="POST" action="{{ route('admin.navigation.items.store', $menu) }}" class="a-card a-card-body h-fit space-y-4">
            @csrf
            <h2 class="a-card-title">Add a link</h2>
            @php($isNew = old('_item') === null)
            <x-admin.input name="label" label="Label" required :value="$isNew ? old('label') : null" id="new-label" maxlength="120" />
            <x-admin.input name="url" label="Destination" required :value="$isNew ? old('url') : null" id="new-url" placeholder="/services or https://…" />
            @if ($allowsChildren)
                <x-admin.select name="parent_id" label="Place under" :options="$parents" :value="$isNew ? old('parent_id') : null" placeholder="Top level" id="new-parent" />
                <x-admin.input name="description" label="Short description" hint="Shown in dropdown menus." :value="$isNew ? old('description') : null" id="new-description" maxlength="200" />
            @endif
            <x-admin.toggle name="open_in_new_tab" label="Open in a new tab" id="new-tab" />
            <x-admin.toggle name="is_enabled" label="Enabled" :checked="true" id="new-enabled" />
            <button type="submit" class="a-btn a-btn-primary w-full">Add link</button>
        </form>
    </div>
@endsection
