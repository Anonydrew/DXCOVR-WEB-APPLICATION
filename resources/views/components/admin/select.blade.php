@props(['name', 'label', 'options' => [], 'value' => null, 'hint' => null, 'required' => false, 'id' => null, 'placeholder' => null])
@php
    $id ??= 'f-'.str_replace(['[', ']', '.'], ['-', '', '-'], $name);
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $error = $errors->first($key);
    $selected = old($key, $value instanceof \BackedEnum ? $value->value : $value);
    $selected = $selected === null ? '' : (string) $selected;
@endphp
<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="a-label">{{ $label }} @if ($required)<span class="text-lime" aria-hidden="true">*</span>@endif</label>
    <select
        id="{{ $id }}"
        name="{{ $name }}"
        @if ($required) required @endif
        @if ($error) aria-invalid="true" @endif
        @if ($hint || $error) aria-describedby="{{ trim(($hint ? $id.'-hint ' : '').($error ? $id.'-error' : '')) }}" @endif
        {{ $attributes->except('class')->merge(['class' => 'a-input']) }}
    >
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @if ($hint)
        <p id="{{ $id }}-hint" class="a-hint">{{ $hint }}</p>
    @endif
    @if ($error)
        <p id="{{ $id }}-error" class="a-error">{{ $error }}</p>
    @endif
</div>
