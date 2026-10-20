@props(['palette'])
<span {{ $attributes->merge(['class' => 'ob-palette']) }} role="img" aria-label="Colours: {{ implode(', ', $palette['colours']) }}">
    @foreach ($palette['colours'] as $colour)
        <span style="background: {{ $colour }}"></span>
    @endforeach
</span>
