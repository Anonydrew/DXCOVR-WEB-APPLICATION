@props(['type'])
<span {{ $attributes->merge(['class' => 'ob-type-sample ob-font-'.$type['font']]) }} aria-hidden="true">
    <span class="ob-type-sample-glyph">Aa</span>
    <span class="ob-type-sample-text">Built with care for the people we serve.</span>
</span>
