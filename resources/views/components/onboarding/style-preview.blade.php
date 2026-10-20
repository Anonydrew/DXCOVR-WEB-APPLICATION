@props(['preset'])
{{-- An original, abstract page mock-up for one visual style. Decorative: the style name is given in text alongside. --}}
<span {{ $attributes->merge(['class' => 'ob-style-preview ob-font-'.$preset['font']]) }} style="--sp-bg: {{ $preset['bg'] }}; --sp-surface: {{ $preset['surface'] }}; --sp-ink: {{ $preset['ink'] }}; --sp-accent: {{ $preset['accent'] }}; --sp-radius: {{ $preset['radius'] }};" aria-hidden="true">
    <span class="ob-sp-nav">
        <span class="ob-sp-logo"></span>
        <span class="ob-sp-line w-3"></span><span class="ob-sp-line w-3"></span><span class="ob-sp-line w-3"></span>
    </span>
    <span class="ob-sp-hero">
        <span class="ob-sp-copy">
            <span class="ob-sp-headline">Your brand</span>
            <span class="ob-sp-line w-full"></span>
            <span class="ob-sp-line w-3/4"></span>
            <span class="ob-sp-button"></span>
        </span>
        <span class="ob-sp-media"></span>
    </span>
    <span class="ob-sp-cards"><span></span><span></span><span></span></span>
</span>
