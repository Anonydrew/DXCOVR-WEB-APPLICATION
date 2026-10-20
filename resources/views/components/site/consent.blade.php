@props(['name' => 'consent', 'notice', 'id' => null])
@php
    $id ??= 'f-'.$name;
    $error = $errors->first($name);
    $privacyPage = \App\Models\Page::query()->whereNull('system_key')->where('slug', 'privacy-policy')->published()->first(['id', 'slug']);
@endphp
<div {{ $attributes->only('class') }}>
    <div class="flex items-start gap-3">
        <input type="checkbox" id="{{ $id }}" name="{{ $name }}" value="1" class="checkbox" required @checked(old($name)) @if ($error) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif>
        <label for="{{ $id }}" class="text-sm leading-relaxed text-muted">
            {{ $notice }}
            @if ($privacyPage)
                Read our <a href="{{ route('pages.show', $privacyPage) }}" class="link-underline font-medium text-text" target="_blank" rel="noopener">privacy policy</a>.
            @endif
            <span class="accent" aria-hidden="true">*</span>
        </label>
    </div>
    @if ($error)
        <p id="{{ $id }}-error" class="field-error ml-8">{{ $error }}</p>
    @endif
</div>
