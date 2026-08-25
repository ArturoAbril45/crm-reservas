<x-app-layout>
    <x-slot name="header">
        Sucursales
    </x-slot>

    <div x-data="{
            abierto: false,
            id: null,
            nombre: '',
            direccion: '',
            telefono: '',
            activa: true,
            editar(sucursal) {
                this.id = sucursal.id;
                this.nombre = sucursal.nombre;
                this.direccion = sucursal.direccion;
                this.telefono = sucursal.telefono;
                this.activa = sucursal.activa;
                this.abierto = true;
            },
         }">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Lista -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            @if (session('status'))
                <div class="px-6 py-3 bg-blue-50 text-blue-700 text-sm border-b border-blue-100">
                    {{ session('status') }}
                </div>
            @endif

            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Nombre</th>
                        <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Dirección</th>
                        <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Teléfono</th>
                        <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sucursales as $sucursal)
                        <tr class="border-b border-gray-50 last:border-0">
                            <td class="px-6 py-4 font-medium text-gray-900">{{ $sucursal->nombre }}</td>
                            <td class="px-6 py-4 text-gray-500">{{ $sucursal->direccion ?? '—' }}</td>
                            <td class="px-6 py-4 text-gray-500">{{ $sucursal->telefono ?? '—' }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $sucursal->activa ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $sucursal->activa ? 'Activa' : 'Inactiva' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button @click="editar({{ \Illuminate\Support\Js::from($sucursal->only(['id', 'nombre', 'direccion', 'telefono', 'activa'])) }})"
                                        class="text-sm font-medium text-blue-700 hover:text-blue-800">
                                    Editar
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-gray-400">Todavía no hay sucursales.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Formulario -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 h-fit">
            <h2 class="font-semibold text-gray-900 mb-4">Nueva sucursal</h2>

            <form method="POST" action="{{ route('sucursales.store') }}" class="space-y-4">
                @csrf

                <div>
                    <x-input-label for="nombre" value="Nombre" />
                    <x-text-input id="nombre" name="nombre" type="text" class="mt-1.5" required :value="old('nombre')" placeholder="Ej. Casa Blanca" />
                    <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="direccion" value="Dirección (opcional)" />
                    <x-text-input id="direccion" name="direccion" type="text" class="mt-1.5" :value="old('direccion')" />
                    <x-input-error :messages="$errors->get('direccion')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="telefono" value="Teléfono (opcional)" />
                    <x-text-input id="telefono" name="telefono" type="text" class="mt-1.5" :value="old('telefono')" />
                    <x-input-error :messages="$errors->get('telefono')" class="mt-2" />
                </div>

                <x-primary-button>Crear sucursal</x-primary-button>
            </form>
        </div>
    </div>

    <!-- Modal editar sucursal -->
    <div x-show="abierto" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4" style="display: none;">
        <div @click.outside="abierto = false" class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900">Editar sucursal</h3>
                <button @click="abierto = false" class="text-gray-400 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form method="POST" :action="'{{ url('/sucursales') }}/' + id" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <x-input-label for="edit_nombre" value="Nombre" />
                    <x-text-input id="edit_nombre" name="nombre" type="text" class="mt-1.5" required x-model="nombre" />
                </div>

                <div>
                    <x-input-label for="edit_direccion" value="Dirección (opcional)" />
                    <x-text-input id="edit_direccion" name="direccion" type="text" class="mt-1.5" x-model="direccion" />
                </div>

                <div>
                    <x-input-label for="edit_telefono" value="Teléfono (opcional)" />
                    <x-text-input id="edit_telefono" name="telefono" type="text" class="mt-1.5" x-model="telefono" />
                </div>

                <label class="flex items-center justify-between px-3.5 py-2.5 rounded-lg border border-gray-200 cursor-pointer">
                    <span class="text-sm text-gray-700">Sucursal activa</span>
                    <span class="relative inline-flex items-center">
                        <input type="checkbox" name="activa" value="1" x-model="activa" class="peer sr-only">
                        <span class="h-5 w-9 rounded-full bg-gray-200 peer-checked:bg-blue-700 transition-colors"></span>
                        <span class="absolute left-0.5 top-0.5 h-4 w-4 rounded-full bg-white transition-transform peer-checked:translate-x-4"></span>
                    </span>
                </label>

                <div class="flex gap-3 pt-2">
                    <button type="button" @click="abierto = false" class="flex-1 py-2.5 rounded-lg border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <x-primary-button class="flex-1 justify-center">Guardar</x-primary-button>
                </div>
            </form>
        </div>
    </div>

    </div>
</x-app-layout>
