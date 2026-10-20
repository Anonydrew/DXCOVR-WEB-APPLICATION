@props(['name' => 'software', 'size' => 64])
{{-- Original abstract motifs for each DXCOVR discipline. Decorative. --}}
<svg {{ $attributes->merge(['width' => $size, 'height' => $size, 'viewBox' => '0 0 64 64', 'fill' => 'none', 'aria-hidden' => 'true', 'focusable' => 'false']) }}>
    <g stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        @switch($name)
            @case('ai')
                <circle cx="32" cy="32" r="5" fill="#A6EC46" stroke="none" />
                <circle cx="14" cy="18" r="3.5" /><circle cx="50" cy="16" r="3.5" /><circle cx="12" cy="44" r="3.5" />
                <circle cx="52" cy="46" r="3.5" /><circle cx="32" cy="8" r="2.5" /><circle cx="32" cy="56" r="2.5" />
                <path d="M17 20l11 9M47 18l-11 11M15 42l13-7M49 44l-13-9M32 10.5V27M32 37v16.5" opacity=".7" />
                <path d="M17.5 18h29M15.5 44h33" opacity=".35" stroke-dasharray="2 4" />
                @break
            @case('hardware')
                <rect x="18" y="18" width="28" height="28" rx="4" />
                <rect x="26" y="26" width="12" height="12" rx="2" fill="#A6EC46" stroke="none" />
                <path d="M24 18V8M32 18V6M40 18V8M24 46v10M32 46v12M40 46v10M18 24H8M18 32H6M18 40H8M46 24h10M46 32h12M46 40h10" />
                <circle cx="24" cy="8" r="1.5" /><circle cx="40" cy="56" r="1.5" /><circle cx="56" cy="24" r="1.5" />
                @break
            @case('xr')
                <path d="M8 26c0-5 4-8 9-8h30c5 0 9 3 9 8v8c0 5-4 9-9 9h-7l-4-5h-8l-4 5h-7c-5 0-9-4-9-9z" />
                <circle cx="22" cy="30" r="5" /><circle cx="42" cy="30" r="5" fill="#A6EC46" stroke="none" />
                <path d="M4 30h4M56 30h4M14 54c5-3 11-4.5 18-4.5S45 51 50 54" opacity=".5" />
                @break
            @case('webxr')
                <circle cx="32" cy="32" r="22" opacity=".55" />
                <ellipse cx="32" cy="32" rx="9" ry="22" opacity=".55" />
                <path d="M10 32h44" opacity=".55" />
                <path d="M32 21l10 5.5v11L32 43l-10-5.5v-11z" />
                <path d="M22 26.5 32 32l10-5.5M32 32v11" />
                <circle cx="32" cy="32" r="2.5" fill="#A6EC46" stroke="none" />
                @break
            @case('modelling')
                <path d="M32 6 54 18.5v27L32 58 10 45.5v-27z" />
                <path d="M10 18.5 32 31l22-12.5M32 31v27" />
                <path d="M21 12.3 43 24.8M43 12.3 21 24.8M21 50.8V37.2M43 50.8V37.2" opacity=".35" stroke-dasharray="2 3" />
                <circle cx="32" cy="31" r="3" fill="#A6EC46" stroke="none" />
                @break
            @case('design')
                <rect x="8" y="8" width="48" height="48" rx="4" opacity=".4" />
                <path d="M8 24h48M24 8v48" opacity=".4" />
                <circle cx="24" cy="24" r="11" />
                <path d="M30 50 50 30l4 4-20 20h-4z" />
                <path d="M46 34l4 4" />
                <rect x="21" y="21" width="6" height="6" rx="1" fill="#A6EC46" stroke="none" />
                @break
            @case('support')
                <path d="M32 6 52 14v16c0 13-8.5 22-20 28C20.5 52 12 43 12 30V14z" />
                <path d="M23 31l6 6 12-13" />
                <circle cx="32" cy="6" r="2.5" fill="#A6EC46" stroke="none" />
                @break
            @default
                <rect x="6" y="12" width="40" height="30" rx="4" />
                <rect x="18" y="22" width="40" height="30" rx="4" fill="currentColor" fill-opacity=".04" />
                <path d="M6 20h40M30 33l-5 4 5 4M46 33l5 4-5 4M40 31l-4 12" />
                <circle cx="11" cy="16" r="1.2" /><circle cx="15.5" cy="16" r="1.2" />
                <circle cx="19.5" cy="16" r="1.2" fill="#A6EC46" stroke="none" />
        @endswitch
    </g>
</svg>
