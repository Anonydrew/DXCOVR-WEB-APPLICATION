@props(['name', 'label', 'options' => [], 'selected' => [], 'hint' => null, 'columns' => 2])
@php
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $chosen = collect(old($key, $selected))->map(fn ($value) => (string) $value)->all();
    $error = $errors->first($key) ?: $errors->first($key.'.*');
@endphp
<fieldset {{ $attributes }}>
    <legend class="a-label">{{ $label }}</legend>
    @if ($hint)
        <p class="a-hint -mt-1 mb-2">{{ $hint }}</p>
    @endif
    <input type="hidden" name="{{ $name }}" value="">
    @if (count($options) === 0)
        <p class="text-sm a-subtle">Nothing to choose from yet.</p>
    @else
        <div class="grid max-h-64 gap-2 overflow-y-auto rounded-lg border border-line bg-ink-2 p-3 {{ $columns === 1 ? '' : 'sm:grid-cols-2' }}">
            @foreach ($options as $value => $optionLabel)
                <label class="flex items-start gap-2.5 text-sm">
                    <input type="checkbox" name="{{ $name }}[]" value="{{ $value }}" class="a-checkbox" @checked(in_array((string) $value, $chosen, true))>
                    <span>{{ $optionLabel }}</span>
                </label>
            @endforeach
        </div>
    @endif
    @if ($error)
        <p class="a-error">{{ $error }}</p>
    @endif
</fieldset>
