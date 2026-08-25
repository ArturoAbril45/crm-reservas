<x-app-layout>
    <x-slot name="header">
        {{ $seccion }}
    </x-slot>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 flex flex-col items-center justify-center text-center gap-3">
        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-50 text-blue-700">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                <rect x="3" y="4" width="18" height="16" rx="2" />
                <path d="M3 9h18" />
                <path d="M9 21V9" />
            </svg>
        </span>
        <h2 class="text-lg font-semibold text-gray-900">{{ $seccion }}</h2>
        <p class="text-sm text-gray-500 max-w-sm">Esta sección todavía no tiene contenido — la armamos en el próximo paso.</p>
    </div>
</x-app-layout>
