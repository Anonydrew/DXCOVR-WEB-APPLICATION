@props(['name', 'label', 'required' => false, 'hint' => null, 'value' => null, 'id' => null, 'rows' => 6])
@php
    $id ??= 'f-'.str_replace(['[', ']', '.'], '-', $name);
    $error = $errors->first($name);
    $describedBy = trim(($hint ? $id.'-hint ' : '').($error ? $id.'-error' : ''));
@endphp
<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="field-label">
        {{ $label }}
        @if ($required)<span class="accent" aria-hidden="true">*</span>@else<span class="font-normal text-subtle"> (optional)</span>@endif
    </label>
    <textarea
        id="{{ $id }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        @if ($required) required @endif
        @if ($error) aria-invalid="true" @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->except('class')->merge(['class' => 'field']) }}
    >{{ old($name, $value) }}</textarea>
    @if ($hint)
        <p id="{{ $id }}-hint" class="field-hint">{{ $hint }}</p>
    @endif
    @if ($error)
        <p id="{{ $id }}-error" class="field-error">{{ $error }}</p>
    @endif
</div>
