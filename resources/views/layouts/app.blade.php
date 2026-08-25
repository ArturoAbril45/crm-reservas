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
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-50">
            @include('layouts.navigation')

            <div class="lg:ml-[272px]">
                <!-- Barra superior -->
                <header class="hidden lg:flex items-center justify-between h-16 px-6 mt-6 mr-4 bg-white rounded-2xl shadow-sm border border-gray-100">
                    <div>
                        @isset($header)
                            <h1 class="font-semibold text-lg text-gray-900">{{ $header }}</h1>
                        @endisset
                    </div>

                    <div class="flex items-center gap-4">
                        <button type="button" class="relative flex items-center justify-center h-10 w-10 rounded-full bg-gray-50 text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-colors" title="Notificaciones">
                            <x-nav-icon name="bell" class="h-[19px] w-[19px]" />
                            {{-- Sin notificaciones por ahora — el punto aparece solo cuando haya alguna --}}
                        </button>

                        <div class="h-6 w-px bg-gray-200"></div>

                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <button class="flex items-center gap-2 text-sm font-medium text-gray-700 hover:text-gray-900">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-50 text-blue-700 font-semibold text-xs">
                                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                    </span>
                                    {{ Auth::user()->name }}
                                    <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <x-dropdown-link :href="route('profile.edit')">
                                    {{ __('Perfil') }}
                                </x-dropdown-link>
                            </x-slot>
                        </x-dropdown>
                    </div>
                </header>

                <!-- Encabezado móvil -->
                @isset($header)
                    <div class="lg:hidden bg-white border-b border-gray-100 px-4 py-4">
                        <h1 class="font-semibold text-lg text-gray-900">{{ $header }}</h1>
                    </div>
                @endisset

                <!-- Contenido -->
                <main class="p-4 lg:p-6">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
