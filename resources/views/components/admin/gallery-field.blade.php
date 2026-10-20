@props(['name', 'label', 'attachments' => collect(), 'hint' => null])
@php
    $items = $attachments->map(fn ($attachment) => array_merge(
        \App\Http\Resources\MediaPickerResource::make($attachment->media)->resolve(),
        ['caption' => $attachment->caption ?? ''],
    ))->values()->all();
@endphp
<div {{ $attributes }} x-data="galleryField({ items: @js($items) })">
    <div class="flex items-center justify-between gap-3">
        <p class="a-label">{{ $label }}</p>
        <button type="button" class="a-btn a-btn-sm" @click="add()"><x-icon name="plus" :size="14" /> Add images</button>
    </div>
    @if ($hint)
        <p class="a-hint -mt-1 mb-2">{{ $hint }}</p>
    @endif
    <input type="hidden" name="{{ $name }}" value="" x-show="false">
    <ul x-ref="list" class="space-y-2">
        <template x-for="(item, index) in items" :key="item.id">
            <li class="flex items-center gap-3 rounded-xl border border-line bg-ink-2 p-2.5">
                <span class="a-drag-handle a-icon-btn" aria-hidden="true"><x-icon name="drag" :size="16" /></span>
                <img :src="item.thumb" :alt="item.alt || ''" class="size-14 flex-none rounded-lg object-cover">
                <input type="hidden" :name="`{{ $name }}[${index}][media_id]`" :value="item.id">
                <label class="sr-only" :for="`gallery-caption-${item.id}`">Caption</label>
                <input type="text" :id="`gallery-caption-${item.id}`" :name="`{{ $name }}[${index}][caption]`" x-model="item.caption" class="a-input" placeholder="Caption (optional)" maxlength="255">
                <span x-show="! item.alt" class="hidden flex-none text-xs text-warning sm:block">No alt text</span>
                <button type="button" class="a-icon-btn flex-none" @click="remove(index)" :aria-label="`Remove ${item.name}`"><x-icon name="trash" :size="16" /></button>
            </li>
        </template>
    </ul>
    <p x-show="items.length === 0" class="rounded-xl border border-dashed border-line-strong p-6 text-center text-sm a-subtle">No images yet.</p>
</div>
