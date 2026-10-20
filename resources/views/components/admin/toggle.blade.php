@props(['name', 'label', 'checked' => false, 'hint' => null, 'id' => null])
@php
    $id ??= 'f-'.str_replace(['[', ']', '.'], ['-', '', '-'], $name);
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $isChecked = (bool) old($key, $checked);
    if (old('_token') !== null && old($key) === null) {
        $isChecked = false;
    }
@endphp
<div {{ $attributes->merge(['class' => 'flex items-start gap-3']) }}>
    <input type="hidden" name="{{ $name }}" value="0">
    <input type="checkbox" role="switch" id="{{ $id }}" name="{{ $name }}" value="1" class="a-toggle mt-0.5" @checked($isChecked) @if ($hint) aria-describedby="{{ $id }}-hint" @endif>
    <div>
        <label for="{{ $id }}" class="text-sm font-semibold">{{ $label }}</label>
        @if ($hint)
            <p id="{{ $id }}-hint" class="text-xs a-subtle">{{ $hint }}</p>
        @endif
        @error($key)
            <p class="a-error">{{ $message }}</p>
        @enderror
    </div>
</div>
