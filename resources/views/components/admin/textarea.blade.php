@props(['name', 'label', 'value' => null, 'hint' => null, 'required' => false, 'id' => null, 'rows' => 4, 'counter' => null, 'recommended' => null])
@php
    $id ??= 'f-'.str_replace(['[', ']', '.'], ['-', '', '-'], $name);
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $error = $errors->first($key);
@endphp
<div {{ $attributes->only('class') }} @if ($counter) x-data="charCount({{ (int) $counter }}, {{ $recommended ? (int) $recommended : 'null' }})" @endif>
    <div class="flex items-baseline justify-between gap-3">
        <label for="{{ $id }}" class="a-label">{{ $label }} @if ($required)<span class="text-lime" aria-hidden="true">*</span>@endif</label>
        @if ($counter)
            <span class="text-xs" :class="tone"><span x-text="length"></span>/{{ $recommended ?? $counter }}</span>
        @endif
    </div>
    <textarea
        id="{{ $id }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        @if ($required) required @endif
        @if ($counter) maxlength="{{ $counter }}" @endif
        @if ($error) aria-invalid="true" @endif
        @if ($hint || $error) aria-describedby="{{ trim(($hint ? $id.'-hint ' : '').($error ? $id.'-error' : '')) }}" @endif
        {{ $attributes->except('class')->merge(['class' => 'a-input']) }}
    >{{ old($key, $value) }}</textarea>
    @if ($hint)
        <p id="{{ $id }}-hint" class="a-hint">{{ $hint }}</p>
    @endif
    @if ($error)
        <p id="{{ $id }}-error" class="a-error">{{ $error }}</p>
    @endif
</div>
