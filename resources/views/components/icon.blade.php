@props(['name', 'size' => 20])
{{-- Interface icons (decorative by default; pass aria-label + role="img" to make meaningful). --}}
<svg {{ $attributes->merge(['width' => $size, 'height' => $size, 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.6', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round', 'aria-hidden' => $attributes->has('aria-label') ? null : 'true', 'focusable' => 'false']) }}>
    @switch($name)
        @case('arrow-right')
            <path d="M5 12h14M13 6l6 6-6 6" />
            @break
        @case('arrow-left')
            <path d="M19 12H5M11 6l-6 6 6 6" />
            @break
        @case('arrow-up-right')
            <path d="M7 17 17 7M8 7h9v9" />
            @break
        @case('chevron-down')
            <path d="m6 9 6 6 6-6" />
            @break
        @case('chevron-right')
            <path d="m9 6 6 6-6 6" />
            @break
        @case('menu')
            <path d="M4 7h16M4 12h16M4 17h10" />
            @break
        @case('close')
            <path d="M6 6l12 12M18 6 6 18" />
            @break
        @case('plus')
            <path d="M12 5v14M5 12h14" />
            @break
        @case('minus')
            <path d="M5 12h14" />
            @break
        @case('search')
            <circle cx="11" cy="11" r="6.5" /><path d="m16 16 4 4" />
            @break
        @case('mail')
            <rect x="3" y="5" width="18" height="14" rx="2.5" /><path d="m4 7 8 6 8-6" />
            @break
        @case('phone')
            <path d="M5 4h3.5l1.7 4.3-2.2 1.4a11 11 0 0 0 6.3 6.3l1.4-2.2L20 15.5V19a1 1 0 0 1-1 1A16 16 0 0 1 4 5a1 1 0 0 1 1-1Z" />
            @break
        @case('whatsapp')
            <path d="M4 20l1.3-3.9A8 8 0 1 1 8 19z" /><path d="M9 9.5c.3 2 2.5 4.2 4.5 4.5l1.2-1.2 1.6.8c-.2 1-1 1.7-2 1.7A6 6 0 0 1 8.4 9.4c0-1 .7-1.8 1.7-2l.8 1.6z" />
            @break
        @case('map-pin')
            <path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21Z" /><circle cx="12" cy="9.5" r="2.5" />
            @break
        @case('clock')
            <circle cx="12" cy="12" r="8.5" /><path d="M12 7.5V12l3 2" />
            @break
        @case('calendar')
            <rect x="3.5" y="5" width="17" height="15" rx="2.5" /><path d="M3.5 10h17M8 3v4M16 3v4" />
            @break
        @case('check')
            <path d="m5 12.5 4.5 4.5L19 7.5" />
            @break
        @case('link')
            <path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1" /><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1" />
            @break
        @case('external')
            <path d="M14 4h6v6M20 4l-9 9" /><path d="M18 14v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4" />
            @break
        @case('download')
            <path d="M12 4v11M7 10l5 5 5-5M5 20h14" />
            @break
        @case('upload')
            <path d="M12 20V9M7 14l5-5 5 5M5 4h14" />
            @break
        @case('file')
            <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z" /><path d="M14 3v5h5" />
            @break
        @case('briefcase')
            <rect x="3" y="7" width="18" height="13" rx="2.5" /><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M3 13h18" />
            @break
        @case('globe')
            <circle cx="12" cy="12" r="8.5" /><path d="M3.5 12h17M12 3.5c2.5 2.6 3.5 5.4 3.5 8.5s-1 5.9-3.5 8.5c-2.5-2.6-3.5-5.4-3.5-8.5s1-5.9 3.5-8.5Z" />
            @break
        @case('linkedin')
            <rect x="3" y="3" width="18" height="18" rx="3" /><path d="M8 10.5V17M8 7.5v.01M11.5 17v-3.8a2.2 2.2 0 0 1 4.4 0V17M11.5 10.5V17" />
            @break
        @case('x')
            <path d="M4 4l16 16M20 4 4 20" />
            @break
        @case('github')
            <path d="M9 19c-4 1.3-4-2-6-2.5M15 21v-3.5a3 3 0 0 0-.9-2.4c3-.3 6.1-1.5 6.1-6.6a5.1 5.1 0 0 0-1.4-3.6 4.8 4.8 0 0 0-.1-3.6s-1.1-.3-3.7 1.4a12.8 12.8 0 0 0-6.7 0C5.7 1.4 4.6 1.7 4.6 1.7a4.8 4.8 0 0 0-.1 3.6A5.1 5.1 0 0 0 3 8.9c0 5.1 3.1 6.3 6.1 6.6a3 3 0 0 0-.9 2.4V21" />
            @break
        @case('instagram')
            <rect x="3.5" y="3.5" width="17" height="17" rx="4.5" /><circle cx="12" cy="12" r="3.8" /><path d="M17 7v.01" />
            @break
        @case('facebook')
            <path d="M14.5 8H17V4.5h-2.5A4 4 0 0 0 10.5 8.5V11H8v3.5h2.5V21H14v-6.5h2.6l.4-3.5h-3V9a1 1 0 0 1 1-1Z" />
            @break
        @case('youtube')
            <rect x="2.5" y="5.5" width="19" height="13" rx="3.5" /><path d="m10 9.5 5 2.5-5 2.5z" />
            @break
        @case('behance')
            <path d="M3 7h5a2.5 2.5 0 0 1 0 5H3zM3 12h5.5a2.5 2.5 0 0 1 0 5H3zM14 13.5h7a3.5 3.5 0 0 0-7 0 3.5 3.5 0 0 0 6.5 1.8M15 7.5h5" />
            @break
        @case('dribbble')
            <circle cx="12" cy="12" r="8.5" /><path d="M6 5.6c3.8 3.4 7.6 8.6 9.2 14.4M3.7 10.5c5 .4 10.6-.6 14.6-4.4M8.5 20c1.7-4 6-6.8 11.9-6.1" />
            @break
        @case('sparkle')
            <path d="M12 3v4M12 17v4M3 12h4M17 12h4M6 6l2.5 2.5M15.5 15.5 18 18M18 6l-2.5 2.5M8.5 15.5 6 18" />
            @break
        @case('share')
            <circle cx="18" cy="5.5" r="2.5" /><circle cx="6" cy="12" r="2.5" /><circle cx="18" cy="18.5" r="2.5" /><path d="m8.2 10.8 7.6-4.1M8.2 13.2l7.6 4.1" />
            @break
        @case('play')
            <path d="M7 4.5v15l12-7.5z" />
            @break
        @case('pause')
            <path d="M8.5 5v14M15.5 5v14" />
            @break
        @case('lock')
            <rect x="4.5" y="10.5" width="15" height="10" rx="2.5" /><path d="M8 10.5V7a4 4 0 0 1 8 0v3.5" />
            @break
        @case('grid')
            <rect x="3.5" y="3.5" width="7" height="7" rx="1.5" /><rect x="13.5" y="3.5" width="7" height="7" rx="1.5" /><rect x="3.5" y="13.5" width="7" height="7" rx="1.5" /><rect x="13.5" y="13.5" width="7" height="7" rx="1.5" />
            @break
        @case('layers')
            <path d="m12 3 9 5-9 5-9-5z" /><path d="m3 13 9 5 9-5" />
            @break
        @case('box')
            <path d="m12 3 8.5 4.5v9L12 21l-8.5-4.5v-9z" /><path d="m3.5 7.5 8.5 4.5 8.5-4.5M12 12v9" />
            @break
        @case('tag')
            <path d="M3.5 12.5V4.5a1 1 0 0 1 1-1h8l8 8-9 9z" /><circle cx="8.5" cy="8.5" r="1.5" />
            @break
        @case('users')
            <circle cx="9" cy="8" r="3.5" /><path d="M2.5 20a6.5 6.5 0 0 1 13 0" /><path d="M16 4.6a3.5 3.5 0 0 1 0 6.8M18 14a6.5 6.5 0 0 1 3.5 6" />
            @break
        @case('user')
            <circle cx="12" cy="8" r="4" /><path d="M4.5 20.5a7.5 7.5 0 0 1 15 0" />
            @break
        @case('settings')
            <circle cx="12" cy="12" r="3" /><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z" />
            @break
        @case('image')
            <rect x="3.5" y="4.5" width="17" height="15" rx="2.5" /><circle cx="9" cy="10" r="1.8" /><path d="m4 18 5-5 4 4 2.5-2.5L20 19" />
            @break
        @case('inbox')
            <path d="M3.5 13.5 6 5h12l2.5 8.5V19a1.5 1.5 0 0 1-1.5 1.5H5A1.5 1.5 0 0 1 3.5 19z" /><path d="M3.5 13.5H9l1 2h4l1-2h5.5" />
            @break
        @case('shield')
            <path d="M12 3 19.5 6v6c0 4.5-3.2 7.8-7.5 9-4.3-1.2-7.5-4.5-7.5-9V6z" />
            @break
        @case('history')
            <path d="M3.5 12a8.5 8.5 0 1 0 2.5-6" /><path d="M3.5 4v4h4M12 7.5V12l3 2" />
            @break
        @case('quote')
            <path d="M5 18v-5c0-3.5 2-5.5 5-6M14 18v-5c0-3.5 2-5.5 5-6" /><path d="M5 13h5v5H5zM14 13h5v5h-5z" />
            @break
        @case('help')
            <circle cx="12" cy="12" r="8.5" /><path d="M9.5 9.5a2.5 2.5 0 1 1 3.5 2.3c-.6.3-1 .9-1 1.6v.6M12 17v.01" />
            @break
        @case('megaphone')
            <path d="M4 10v4h3l7 4V6L7 10z" /><path d="M17.5 9a4 4 0 0 1 0 6M7 14l1.5 5.5H11L10 15" />
            @break
        @case('logout')
            <path d="M15 4h3.5A1.5 1.5 0 0 1 20 5.5v13a1.5 1.5 0 0 1-1.5 1.5H15M10 16l-4-4 4-4M6 12h10" />
            @break
        @case('eye')
            <path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z" /><circle cx="12" cy="12" r="3" />
            @break
        @case('edit')
            <path d="M4 20h4L19 9l-4-4L4 16z" /><path d="m13.5 6.5 4 4" />
            @break
        @case('trash')
            <path d="M4 7h16M10 11v6M14 11v6M5.5 7l1 12.5A1.5 1.5 0 0 0 8 21h8a1.5 1.5 0 0 0 1.5-1.5l1-12.5M9 7V4.5A1.5 1.5 0 0 1 10.5 3h3A1.5 1.5 0 0 1 15 4.5V7" />
            @break
        @case('drag')
            <circle cx="9" cy="6" r="1.2" fill="currentColor" /><circle cx="15" cy="6" r="1.2" fill="currentColor" /><circle cx="9" cy="12" r="1.2" fill="currentColor" /><circle cx="15" cy="12" r="1.2" fill="currentColor" /><circle cx="9" cy="18" r="1.2" fill="currentColor" /><circle cx="15" cy="18" r="1.2" fill="currentColor" />
            @break
        @case('copy')
            <rect x="8.5" y="8.5" width="12" height="12" rx="2" /><path d="M15.5 8.5V5a1.5 1.5 0 0 0-1.5-1.5H5A1.5 1.5 0 0 0 3.5 5v9A1.5 1.5 0 0 0 5 15.5h3.5" />
            @break
        @case('filter')
            <path d="M3.5 5h17l-6.5 8v6l-4-2v-4z" />
            @break
        @case('star')
            <path d="m12 3.5 2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.8l-5.2 2.8 1-5.8-4.3-4.1 5.9-.9z" />
            @break
        @case('navigation')
            <path d="M4 6h16M4 12h10M4 18h6" /><circle cx="18" cy="16" r="3" />
            @break
        @case('redirect')
            <path d="M4 7h11a5 5 0 0 1 0 10H8" /><path d="m12 3 4 4-4 4M8 13l-4 4 4 4" />
            @break
        @case('arrow-up')
            <path d="M12 19V5M6 11l6-6 6 6" />
            @break
        @case('arrow-down')
            <path d="M12 5v14M6 13l6 6 6-6" />
            @break
        @case('alert')
            <path d="M12 3.5 21.5 20h-19z" /><path d="M12 10v4.5M12 17.5v.01" />
            @break
        @case('send')
            <path d="M21 3 10 14M21 3l-7 18-4-7-7-4z" />
            @break
        @default
            <circle cx="12" cy="12" r="8.5" />
    @endswitch
</svg>
