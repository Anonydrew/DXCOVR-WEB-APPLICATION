@props(['name', 'label', 'items' => [], 'hint' => null, 'placeholder' => 'Add an item', 'max' => 30])
@php
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $values = array_values(array_filter((array) old($key, $items ?? []), fn ($value) => $value !== null && $value !== ''));
    $error = $errors->first($key) ?: $errors->first($key.'.*');
@endphp
<div {{ $attributes }} x-data="listField(@js($values))">
    <p class="a-label">{{ $label }}</p>
    @if ($hint)
        <p class="a-hint -mt-1 mb-2">{{ $hint }}</p>
    @endif
    <input type="hidden" name="{{ $name }}" value="" x-show="false">
    <ul class="mb-2 flex flex-wrap gap-2">
        <template x-for="(item, index) in items" :key="index + item">
            <li class="flex items-center gap-1 rounded-full border border-line-strong bg-ink-2 py-1 pl-3 pr-1 text-sm">
                <input type="hidden" :name="`{{ $name }}[${index}]`" :value="item">
                <span x-text="item"></span>
                <button type="button" class="a-icon-btn !size-6" @click="move(index, -1)" :aria-label="`Move ${item} earlier`"><x-icon name="arrow-left" :size="12" /></button>
                <button type="button" class="a-icon-btn !size-6" @click="remove(index)" :aria-label="`Remove ${item}`"><x-icon name="close" :size="12" /></button>
            </li>
        </template>
    </ul>
    <div class="flex gap-2">
        <label class="sr-only" for="{{ 'f-'.$key }}-draft">{{ $label }}</label>
        <input id="{{ 'f-'.$key }}-draft" type="text" x-model="draft" @keydown.enter.prevent="add()" class="a-input" placeholder="{{ $placeholder }}" maxlength="160">
        <button type="button" class="a-btn" @click="add()" :disabled="items.length >= {{ $max }}">Add</button>
    </div>
    @if ($error)
        <p class="a-error">{{ $error }}</p>
    @endif
</div>
