<x-guest-layout>
    <div x-data="{ cargando: false, sucursalElegida: '' }" class="relative">
        <div class="mb-8">
            <h1 class="text-2xl font-semibold text-gray-900">¿En qué sucursal vas a trabajar?</h1>
            <p class="mt-1.5 text-sm text-gray-500">Elegí una sucursal para continuar.</p>
        </div>

        <div class="space-y-2.5">
            @forelse ($sucursales as $s)
                <form method="POST" action="{{ route('sucursales.cambiar', $s) }}"
                      @submit="cargando = true; sucursalElegida = '{{ addslashes($s->nombre) }}'">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center gap-3 px-4 py-3.5 rounded-xl border border-gray-200 hover:border-[#9c0720]/40 hover:bg-[#9c0720]/5 transition-colors text-left">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#9c0720]/10 text-[#9c0720]">
                            <x-nav-icon name="store" class="h-5 w-5" />
                        </span>
                        <span class="flex-1 min-w-0">
                            <span class="block font-semibold text-gray-900 truncate">{{ $s->nombre }}</span>
                            @if ($s->direccion)
                                <span class="block text-xs text-gray-400 truncate">{{ $s->direccion }}</span>
                            @endif
                        </span>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 text-gray-300 shrink-0">
                            <path d="m9 18 6-6-6-6" />
                        </svg>
                    </button>
                </form>
            @empty
                <p class="text-sm text-gray-400 text-center py-8">Todavía no hay sucursales creadas.</p>
            @endforelse
        </div>

        <!-- Overlay de carga -->
        <div x-show="cargando" x-cloak class="fixed inset-0 z-50 bg-white flex flex-col items-center justify-center gap-4">
            <svg class="animate-spin h-8 w-8 text-[#9c0720]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <p class="text-sm font-medium text-gray-700" x-text="'Entrando a ' + sucursalElegida + '...'"></p>
        </div>
    </div>
</x-guest-layout>
