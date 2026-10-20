@props(['seo' => null, 'fallbackTitle' => '', 'fallbackDescription' => '', 'url' => null])
@php
    $site = app(\App\Services\SiteSettings::class);
    $template = $site->get('seo.title_template', '%s | DXCOVR');
@endphp
<details class="a-card group" {{ $attributes }} @if ($errors->hasAny(['seo.meta_title', 'seo.meta_description', 'seo.canonical_url', 'seo.og_title', 'seo.og_description', 'seo.og_image_id'])) open @endif>
    <summary class="a-card-header cursor-pointer list-none">
        <span>
            <span class="a-card-title">Search & social</span>
            <span class="block text-xs a-subtle">Optional overrides. Leave blank to use the title and summary.</span>
        </span>
        <x-icon name="chevron-down" :size="16" class="transition group-open:rotate-180" />
    </summary>
    <div class="a-card-body space-y-4" x-data="{ title: @js(old('seo.meta_title', $seo?->meta_title) ?? ''), description: @js(old('seo.meta_description', $seo?->meta_description) ?? ''), fallbackTitle: @js($fallbackTitle), fallbackDescription: @js($fallbackDescription) }">
        <div class="a-serp" aria-label="Search result preview">
            <p class="a-serp-url">{{ $url ?? url('/') }}</p>
            <p class="a-serp-title" x-text="@js($template).replace('%s', title || fallbackTitle || 'Page title')"></p>
            <p class="a-serp-desc" x-text="(description || fallbackDescription || 'The page summary will be used as the description.').slice(0, 160)"></p>
        </div>
        <div x-data="charCount(160, 60)">
            <div class="flex items-baseline justify-between"><label for="seo-meta-title" class="a-label">Meta title</label><span class="text-xs" :class="tone"><span x-text="length"></span>/60</span></div>
            <input id="seo-meta-title" name="seo[meta_title]" x-model="title" maxlength="160" class="a-input" value="{{ old('seo.meta_title', $seo?->meta_title) }}">
            @error('seo.meta_title')<p class="a-error">{{ $message }}</p>@enderror
        </div>
        <div x-data="charCount(320, 160)">
            <div class="flex items-baseline justify-between"><label for="seo-meta-description" class="a-label">Meta description</label><span class="text-xs" :class="tone"><span x-text="length"></span>/160</span></div>
            <textarea id="seo-meta-description" name="seo[meta_description]" x-model="description" maxlength="320" rows="3" class="a-input">{{ old('seo.meta_description', $seo?->meta_description) }}</textarea>
            @error('seo.meta_description')<p class="a-error">{{ $message }}</p>@enderror
        </div>
        <x-admin.input name="seo[canonical_url]" label="Canonical URL" type="url" :value="$seo?->canonical_url" hint="Only set this if the same content is published at another address." />
        <div class="grid gap-4 sm:grid-cols-2">
            <x-admin.input name="seo[og_title]" label="Social title" :value="$seo?->og_title" counter="160" />
            <x-admin.textarea name="seo[og_description]" label="Social description" :value="$seo?->og_description" rows="2" counter="320" />
        </div>
        <x-admin.media-field name="seo[og_image_id]" label="Social preview image" :media="$seo?->ogImage" hint="Recommended 1200 × 630 pixels." />
        <x-admin.toggle name="seo[noindex]" label="Hide from search engines (noindex)" :checked="$seo?->noindex" hint="The page stays public but asks search engines not to list it and leaves it out of the sitemap." />
    </div>
</details>
