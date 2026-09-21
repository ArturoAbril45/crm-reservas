<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex">

            <!-- Panel de marca -->
            <div class="hidden lg:flex lg:w-2/5 xl:w-1/3 bg-[#9c0720] flex-col justify-between p-12">
                <a href="/" class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/10">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 text-white">
                            <rect x="3" y="4" width="18" height="18" rx="2" />
                            <path d="M16 2v4M8 2v4M3 10h18" />
                        </svg>
                    </span>
                    <span class="text-lg font-semibold text-white">CRM Reservas</span>
                </a>

                <div>
                    <img src="{{ asset('images/login-illustration.svg') }}" alt="" class="mb-8 w-full max-w-sm">

                    <p class="text-2xl font-semibold leading-snug text-white">
                        Organizá tus reservas en un solo lugar.
                    </p>
                    <p class="mt-3 text-sm text-white/70 max-w-sm">
                        Gestioná clientes, disponibilidad y confirmaciones desde un panel pensado para tu equipo.
                    </p>
                </div>

                <p class="text-xs text-white/50">&copy; {{ date('Y') }} SIDKAP. Todos los derechos reservados.</p>
            </div>

            <!-- Panel de formulario -->
            <div class="flex flex-1 flex-col items-center justify-center px-6 py-12">
                <div class="w-full sm:max-w-sm">
                    <div class="mb-8 flex flex-col items-center gap-2 lg:hidden">
                        <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#9c0720]">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 text-white">
                                <rect x="3" y="4" width="18" height="18" rx="2" />
                                <path d="M16 2v4M8 2v4M3 10h18" />
                            </svg>
                        </span>
                        <span class="text-base font-semibold text-gray-900">CRM Reservas</span>
                    </div>

                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
