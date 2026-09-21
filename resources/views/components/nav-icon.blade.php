@props(['name'])

<svg {{ $attributes->merge(['class' => 'h-5 w-5']) }} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
    @switch($name)
        @case('grid')
            <rect x="3" y="3" width="7" height="7" rx="1.5" />
            <rect x="14" y="3" width="7" height="7" rx="1.5" />
            <rect x="3" y="14" width="7" height="7" rx="1.5" />
            <rect x="14" y="14" width="7" height="7" rx="1.5" />
            @break

        @case('archive')
            <rect x="3" y="4" width="18" height="5" rx="1.5" />
            <path d="M5 9v9a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V9" />
            <path d="M10 13h4" />
            @break

        @case('users')
            <path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2" />
            <circle cx="9" cy="7" r="4" />
            <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
            @break

        @case('tag')
            <path d="M20.59 13.41 11 3.83A2 2 0 0 0 9.59 3.24H4a1 1 0 0 0-1 1v5.59a2 2 0 0 0 .59 1.41l9.58 9.59a2 2 0 0 0 2.83 0l5.59-5.59a2 2 0 0 0 0-2.83Z" />
            <circle cx="7.5" cy="7.5" r="1.2" fill="currentColor" stroke="none" />
            @break

        @case('swap')
            <path d="M7 3 3 7l4 4" />
            <path d="M3 7h13a4 4 0 0 1 4 4v1" />
            <path d="m17 21 4-4-4-4" />
            <path d="M21 17H8a4 4 0 0 1-4-4v-1" />
            @break

        @case('store')
            <path d="M3 21h18" />
            <path d="M5 21V7l8-4v18" />
            <path d="M19 21V11l-6-4" />
            @break

        @case('building')
            <rect x="4" y="3" width="16" height="18" rx="1.5" />
            <path d="M9 8h.01M15 8h.01M9 12h.01M15 12h.01M9 16h.01M15 16h.01" />
            @break

        @case('chart')
            <path d="M3 3v18h18" />
            <rect x="7" y="12" width="3" height="6" rx="0.5" />
            <rect x="13" y="8" width="3" height="10" rx="0.5" />
            <rect x="19" y="5" width="0" height="13" />
            <path d="M18 5h.01" />
            @break

        @case('shield')
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z" />
            <path d="m9 12 2 2 4-4" />
            @break

        @case('bell')
            <path d="M6 8a6 6 0 0 1 12 0c0 5 2 6 2 6H4s2-1 2-6" />
            <path d="M10.5 20a1.5 1.5 0 0 0 3 0" />
            @break

        @case('check')
            <path d="M20 6 9 17l-5-5" />
            @break

        @case('cart')
            <circle cx="9" cy="21" r="1" />
            <circle cx="19" cy="21" r="1" />
            <path d="M2.5 3h2l2.4 12.4a2 2 0 0 0 2 1.6h8.2a2 2 0 0 0 2-1.6L21.5 7H6" />
            @break

        @case('box')
            <path d="m21 8-9-5-9 5 9 5 9-5Z" />
            <path d="M3 8v8l9 5 9-5V8" />
            <path d="M12 13v8" />
            @break

        @case('calendar')
            <rect x="3" y="4" width="18" height="18" rx="2" />
            <path d="M16 2v4M8 2v4M3 10h18" />
            @break

        @case('receipt')
            <path d="M6 2h12v20l-3-2-3 2-3-2-3 2Z" />
            <path d="M9 7h6M9 11h6M9 15h4" />
            @break

        @case('wallet')
            <path d="M20 7H5a2 2 0 0 1 0-4h13v4" />
            <path d="M4 7h16v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7Z" />
            <path d="M16 13h2" />
            @break

        @case('flujo')
            <circle cx="5" cy="6" r="2.5" />
            <circle cx="19" cy="6" r="2.5" />
            <circle cx="12" cy="18" r="2.5" />
            <path d="M7.2 7.3 10 15.8M16.8 7.3 14 15.8M7.5 6h9" />
            @break

        @case('chat')
            <path d="M21 11.5a8.38 8.38 0 0 1-8.5 8.5 8.5 8.5 0 0 1-4.24-1.11L3 20l1.11-5.26A8.38 8.38 0 0 1 3.5 11.5 8.5 8.5 0 0 1 12 3a8.38 8.38 0 0 1 9 8.5Z" />
            @break

        @case('logout')
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
            <path d="M16 17l5-5-5-5" />
            <path d="M21 12H9" />
            @break

        @default
            <circle cx="12" cy="12" r="9" />
    @endswitch
</svg>
