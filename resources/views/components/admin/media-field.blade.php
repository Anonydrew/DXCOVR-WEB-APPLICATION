@props(['name', 'label', 'media' => null, 'hint' => null, 'accept' => 'image'])
@php
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $oldId = old($key);
    if ($oldId !== null && (int) $oldId !== (int) $media?->id) {
        $media = $oldId ? \App\Models\Media::query()->find($oldId) : null;
    }
    $initial = $media ? \App\Http\Resources\MediaPickerResource::make($media)->resolve() : null;
@endphp
<div {{ $attributes }} x-data="mediaField({ initial: @js($initial), accept: @js($accept) })">
    <p class="a-label">{{ $label }}</p>
    <input type="hidden" name="{{ $name }}" :value="media ? media.id : ''">
    <div class="flex items-center gap-4 rounded-xl border border-line bg-ink-2 p-3">
        <div class="a-thumb a-checker size-20 flex-none">
            <template x-if="media && media.is_image"><img :src="media.thumb" :alt="media.alt || ''"></template>
            <template x-if="! media"><span class="grid h-full place-items-center a-subtle"><x-icon name="image" :size="22" /></span></template>
        </div>
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-medium" x-text="media ? media.name : 'No file selected'"></p>
            <p class="text-xs" :class="media && ! media.alt && media.is_image ? 'text-warning' : 'a-subtle'"
               x-text="media ? (media.is_image ? (media.alt ? 'Alt: ' + media.alt : 'Missing alt text. Add it in the media library.') : media.size) : '{{ $hint ? addslashes($hint) : '' }}'"></p>
            <div class="mt-2 flex flex-wrap gap-2">
                <button type="button" class="a-btn a-btn-sm" @click="choose()" x-text="media ? 'Replace' : 'Choose from library'"></button>
                <template x-if="media"><a :href="media.edit_url" class="a-btn a-btn-sm a-btn-ghost" target="_blank">Edit details</a></template>
                <button type="button" class="a-btn a-btn-sm a-btn-ghost" x-show="media" @click="clear()">Remove</button>
            </div>
        </div>
    </div>
    @error($key)
        <p class="a-error">{{ $message }}</p>
    @enderror
</div>
