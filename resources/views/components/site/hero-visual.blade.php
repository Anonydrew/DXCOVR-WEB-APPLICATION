{{--
    The DXCOVR "system" composition: software, intelligence, connected hardware,
    3D and immersive layers orbiting a single core. Inline SVG, no network
    requests, no layout shift, animation disabled for reduced-motion users.
    Pass :minimal="true" to omit the small code and signal cards.
--}}
@props(['minimal' => false])
<div {{ $attributes->merge(['class' => 'hero-visual relative aspect-square w-full']) }} aria-hidden="true">
    <svg viewBox="0 0 560 560" fill="none" class="relative h-full w-full" focusable="false">
        <defs>
            <radialGradient id="hv-core" cx="50%" cy="50%" r="50%">
                <stop offset="0%" stop-color="#C9F78A" />
                <stop offset="55%" stop-color="#A6EC46" />
                <stop offset="100%" stop-color="#6FA12A" />
            </radialGradient>
            <linearGradient id="hv-fade" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stop-color="#F5F7F2" stop-opacity=".55" />
                <stop offset="100%" stop-color="#F5F7F2" stop-opacity=".08" />
            </linearGradient>
        </defs>

        {{-- Measurement grid --}}
        <g stroke="#F5F7F2" stroke-opacity=".06">
            @for ($i = 40; $i < 560; $i += 40)
                <path d="M{{ $i }} 20V540M20 {{ $i }}H540" />
            @endfor
        </g>

        {{-- Outer orbit with discipline nodes --}}
        <circle class="orbit-ring orbit-slow" cx="280" cy="280" r="236" stroke="#F5F7F2" stroke-opacity=".2" stroke-dasharray="2 9" />
        <g>
            <circle cx="280" cy="280" r="236" stroke="url(#hv-fade)" stroke-width="1" />
            <g>
                <circle cx="280" cy="44" r="5" fill="#A6EC46" />
                <circle cx="484" cy="162" r="4" fill="#F5F7F2" />
                <circle cx="484" cy="398" r="4" fill="#F5F7F2" />
                <circle cx="280" cy="516" r="4" fill="#F5F7F2" />
                <circle cx="76" cy="398" r="4" fill="#F5F7F2" />
                <circle cx="76" cy="162" r="4" fill="#F5F7F2" />
            </g>
            @unless ($minimal)
                <g font-family="IBM Plex Mono, monospace" font-size="11" letter-spacing="1.5" fill="#B8BDB4">
                    <text x="294" y="40">SOFTWARE</text>
                    <text x="496" y="158" text-anchor="start">AI</text>
                    <text x="496" y="412">IOT</text>
                    <text x="294" y="530">XR</text>
                    <text x="64" y="412" text-anchor="end">3D</text>
                    <text x="64" y="158" text-anchor="end">UX</text>
                </g>
            @endunless
        </g>

        {{-- Middle orbit --}}
        <g class="orbit-ring orbit-reverse">
            <circle cx="280" cy="280" r="168" stroke="#F5F7F2" stroke-opacity=".14" />
            <path d="M280 112a168 168 0 0 1 145.5 84" stroke="#A6EC46" stroke-width="2" stroke-linecap="round" />
            <rect x="420" y="190" width="12" height="12" rx="2" fill="#151915" stroke="#A6EC46" />
            <rect x="128" y="352" width="10" height="10" rx="2" fill="#151915" stroke="#F5F7F2" stroke-opacity=".6" />
        </g>

        {{-- Wireframe volume (3D / spatial) --}}
        <g stroke="#F5F7F2" stroke-opacity=".55" stroke-width="1.2" stroke-linejoin="round">
            <path d="M280 186 361 233v94l-81 47-81-47v-94z" />
            <path d="M199 233l81 47 81-47M280 280v94" />
            <path d="M239.5 209.5l81 47M320.5 209.5l-81 47M239.5 350.5v-47M320.5 350.5v-47" stroke-opacity=".22" stroke-dasharray="3 5" />
        </g>

        {{-- Circuit traces from core to edge (connected hardware) --}}
        <g stroke="#A6EC46" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" fill="none">
            <path class="trace" d="M280 280h52l24-24h70" stroke-opacity=".75" />
            <path class="trace" d="M280 280v-38l-30-30v-58" stroke-opacity=".5" />
            <path class="trace" d="M280 280h-60l-26 26v62" stroke-opacity=".5" />
        </g>
        <g fill="#080A08" stroke="#A6EC46" stroke-width="1.4">
            <circle cx="426" cy="256" r="5" /><circle cx="250" cy="154" r="5" /><circle cx="194" cy="368" r="5" />
        </g>

        {{-- Core --}}
        <circle class="pulse" cx="280" cy="280" r="42" fill="#A6EC46" fill-opacity=".14" />
        <circle cx="280" cy="280" r="22" fill="url(#hv-core)" />
        <circle cx="280" cy="280" r="30" stroke="#A6EC46" stroke-opacity=".5" />

        @unless ($minimal)
        {{-- Code card (software) --}}
        <g transform="translate(300 404)">
            <rect width="184" height="96" rx="12" fill="#0D100D" stroke="#363D35" />
            <circle cx="16" cy="16" r="3" fill="#363D35" /><circle cx="27" cy="16" r="3" fill="#363D35" /><circle cx="38" cy="16" r="3" fill="#A6EC46" />
            <g font-family="IBM Plex Mono, monospace" font-size="11">
                <text x="16" y="44" fill="#8E948A">const</text><text x="58" y="44" fill="#F5F7F2">system</text><text x="108" y="44" fill="#8E948A">=</text>
                <text x="16" y="62" fill="#A6EC46">connect</text><text x="70" y="62" fill="#F5F7F2">(sensors, model)</text>
                <text x="16" y="80" fill="#B8BDB4">→ ready</text>
            </g>
        </g>

        {{-- Signal readout (IoT telemetry) --}}
        <g transform="translate(40 52)">
            <rect width="168" height="74" rx="12" fill="#0D100D" stroke="#363D35" />
            <text x="14" y="24" font-family="IBM Plex Mono, monospace" font-size="10" letter-spacing="1.2" fill="#8E948A">SIGNAL / 01</text>
            <path d="M14 52c10 0 10-14 20-14s10 22 20 22 10-30 20-30 10 20 20 20 10-10 20-10 10 8 20 8 10-4 20-4" stroke="#A6EC46" stroke-width="1.6" stroke-linecap="round" />
        </g>
        @endunless
    </svg>
</div>
