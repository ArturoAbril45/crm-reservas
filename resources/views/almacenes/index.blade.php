<x-app-layout>
    <x-slot name="header">
        Almacenes
    </x-slot>

    @if (! $sucursal)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center text-gray-500">
            Primero creá una sucursal para poder agregar almacenes.
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Lista -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                @if (session('status'))
                    <div class="px-6 py-3 bg-blue-50 text-blue-700 text-sm border-b border-blue-100">
                        {{ session('status') }}
                    </div>
                @endif

                <div class="px-6 py-4 border-b border-gray-100">
                    <p class="text-xs text-gray-400 uppercase tracking-wider">Sucursal</p>
                    <p class="font-semibold text-gray-900">{{ $sucursal->nombre }}</p>
                </div>

                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Nombre</th>
                            <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Ubicación</th>
                            <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Productos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($almacenes as $almacen)
                            <tr class="border-b border-gray-50 last:border-0">
                                <td class="px-6 py-4 font-medium text-gray-900">{{ $almacen->nombre }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ $almacen->ubicacion ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
                                        {{ $almacen->inventarios_count }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-10 text-center text-gray-400">Todavía no hay almacenes en esta sucursal.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Formulario -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 h-fit">
                <h2 class="font-semibold text-gray-900 mb-4">Nuevo almacén</h2>

                <form method="POST" action="{{ route('almacenes.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="nombre" value="Nombre" />
                        <x-text-input id="nombre" name="nombre" type="text" class="mt-1.5" required :value="old('nombre')" placeholder="Ej. Almacén principal" />
                        <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="ubicacion" value="Ubicación (opcional)" />
                        <x-text-input id="ubicacion" name="ubicacion" type="text" class="mt-1.5" :value="old('ubicacion')" />
                        <x-input-error :messages="$errors->get('ubicacion')" class="mt-2" />
                    </div>

                    <x-primary-button>Crear almacén</x-primary-button>
                </form>
            </div>
        </div>
    @endif
</x-app-layout>
