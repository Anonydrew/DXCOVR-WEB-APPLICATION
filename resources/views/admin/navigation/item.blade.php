<div x-data="{ editing: {{ (string) old('_item') === (string) $item->id ? 'true' : 'false' }} }">
    <div class="flex items-center gap-3 px-3 py-2.5" x-show="! editing">
        <span class="a-drag-handle a-icon-btn" aria-hidden="true"><x-icon name="drag" :size="16" /></span>
        <div class="min-w-0 flex-1">
            <p class="font-semibold {{ $item->is_enabled ? '' : 'a-subtle line-through' }}">{{ $item->label }}</p>
            <p class="truncate text-xs a-subtle">{{ $item->url }}@if ($item->open_in_new_tab) · new tab @endif</p>
        </div>
        @unless ($item->is_enabled)
            <span class="a-badge a-badge-muted">Disabled</span>
        @endunless
        <button type="button" class="a-icon-btn" @click="editing = true" aria-label="Edit {{ $item->label }}"><x-icon name="edit" :size="16" /></button>
        <x-admin.delete-button :action="route('admin.navigation.items.destroy', $item)" :confirm="$item->children->isNotEmpty() ? 'Remove this item and the links nested under it?' : 'Remove this link?'" label="" :aria-label="'Remove '.$item->label" />
    </div>
    <form method="POST" action="{{ route('admin.navigation.items.update', $item) }}" x-show="editing" x-cloak class="grid gap-3 p-4 sm:grid-cols-2">
        @csrf
        @method('PUT')
        <input type="hidden" name="_item" value="{{ $item->id }}">
        <x-admin.input name="label" label="Label" :value="$item->label" required :id="'label-'.$item->id" />
        <x-admin.input name="url" label="Destination" :value="$item->url" required :id="'url-'.$item->id" />
        @if ($allowsChildren && $item->children->isEmpty())
            <x-admin.select name="parent_id" label="Place under" :options="$parents->except($item->id)" :value="$item->parent_id" placeholder="Top level" :id="'parent-'.$item->id" />
        @endif
        @if ($allowsChildren)
            <x-admin.input name="description" label="Short description" :value="$item->description" :id="'description-'.$item->id" />
        @endif
        <x-admin.toggle name="open_in_new_tab" label="Open in a new tab" :checked="$item->open_in_new_tab" :id="'tab-'.$item->id" />
        <x-admin.toggle name="is_enabled" label="Enabled" :checked="$item->is_enabled" :id="'enabled-'.$item->id" />
        <div class="flex gap-2 sm:col-span-2">
            <button type="submit" class="a-btn a-btn-primary a-btn-sm">Save</button>
            <button type="button" class="a-btn a-btn-sm a-btn-ghost" @click="editing = false">Cancel</button>
        </div>
    </form>
</div>
