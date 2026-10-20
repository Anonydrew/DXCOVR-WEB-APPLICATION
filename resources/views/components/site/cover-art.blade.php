@props(['seed' => 'dxcovr', 'glyph' => 'software', 'label' => null, 'ratio' => '4 / 3'])
{{--
    Deterministic abstract artwork used when a project, product or article has
    no imagery yet. It is clearly abstract and never presented as a screenshot.
--}}
@php
    $hash = crc32((string) $seed);
    $variant = $hash % 4;
    $rotation = ($hash >> 3) % 360;
    $offsetX = 40 + ($hash >> 5) % 60;
    $offsetY = 30 + ($hash >> 8) % 50;
@endphp
<svg {{ $attributes->merge(['class' => 'cover-art h-full w-full', 'viewBox' => '0 0 400 300', 'preserveAspectRatio' => 'xMidYMid slice', 'role' => 'img', 'aria-label' => $label ? 'Abstract illustration for '.$label : 'Abstract illustration']) }} focusable="false">
    <rect width="400" height="300" fill="#121612" />
    <g stroke="#F5F7F2" stroke-opacity=".05">
        @for ($i = 20; $i < 400; $i += 20)
            <path d="M{{ $i }} 0V300" />
        @endfor
        @for ($i = 20; $i < 300; $i += 20)
            <path d="M0 {{ $i }}H400" />
        @endfor
    </g>
    @switch($variant)
        @case(0)
            <g transform="rotate({{ $rotation }} 200 150)" stroke="#A6EC46" fill="none">
                <circle cx="200" cy="150" r="110" stroke-opacity=".25" />
                <circle cx="200" cy="150" r="78" stroke-opacity=".45" stroke-dasharray="3 7" />
                <path d="M90 150a110 110 0 0 1 110-110" stroke-width="2" />
            </g>
            @break
        @case(1)
            <g fill="none" stroke="#F5F7F2" stroke-opacity=".35">
                @for ($i = 0; $i < 9; $i++)
                    <path d="M{{ -40 + $i * 40 }} 300 L{{ 60 + $i * 40 + $offsetX }} 0" />
                @endfor
            </g>
            <rect x="{{ 120 + $offsetX / 2 }}" y="{{ 80 + $offsetY / 3 }}" width="120" height="120" rx="14" fill="#A6EC46" fill-opacity=".1" stroke="#A6EC46" stroke-opacity=".6" />
            @break
        @case(2)
            <g fill="none" stroke="#A6EC46" stroke-opacity=".55" stroke-width="1.4">
                <path d="M0 {{ 180 + $offsetY / 2 }} C 80 {{ 120 - $offsetY }}, 160 {{ 240 }}, 240 {{ 150 }} S 360 {{ 60 + $offsetY }}, 400 {{ 120 }}" />
                <path d="M0 {{ 200 + $offsetY / 2 }} C 80 {{ 140 - $offsetY }}, 160 260, 240 170 S 360 {{ 80 + $offsetY }}, 400 140" stroke-opacity=".25" />
            </g>
            @break
        @default
            <g fill="none" stroke="#F5F7F2" stroke-opacity=".4" stroke-linejoin="round">
                <path d="M200 50 290 102v104l-90 52-90-52V102z" />
                <path d="M110 102l90 52 90-52M200 154v104" stroke-opacity=".25" />
            </g>
    @endswitch
    <g transform="translate({{ 400 - 92 }} {{ 300 - 92 }})" color="#F5F7F2">
        <rect width="72" height="72" rx="14" fill="#080A08" fill-opacity=".7" stroke="#363D35" />
        <x-site.glyph :name="$glyph" :size="48" x="12" y="12" />
    </g>
</svg>
