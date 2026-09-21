<x-app-layout>
    <x-slot name="header">
        Almacenes
    </x-slot>

    @if (! $sucursal)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center text-gray-500">
            Primero creá una sucursal para poder agregar almacenes.
        </div>
    @else
        <div x-data="{
                abierto: false,
                id: null,
                nombre: '',
                ubicacion: '',
                editar(almacen) {
                    this.id = almacen.id;
                    this.nombre = almacen.nombre;
                    this.ubicacion = almacen.ubicacion;
                    this.abierto = true;
                },
             }">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Lista -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                @if (session('status'))
                    <div class="px-6 py-3 bg-[#9c0720]/10 text-[#9c0720] text-sm border-b border-[#9c0720]/15">
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
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($almacenes as $almacen)
                            <tr class="border-b border-gray-50 last:border-0">
                                <td class="px-6 py-4 font-medium text-gray-900">{{ $almacen->nombre }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ $almacen->ubicacion ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-[#9c0720]/10 text-[#9c0720]">
                                        {{ $almacen->inventarios_count }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        <button @click="editar({{ \Illuminate\Support\Js::from($almacen->only(['id', 'nombre', 'ubicacion'])) }})"
                                                class="text-sm font-medium text-[#9c0720] hover:text-[#7c0519]">
                                            Editar
                                        </button>
                                        <form method="POST" action="{{ route('almacenes.destroy', $almacen) }}"
                                              onsubmit="return confirm('¿Eliminar el almacén &quot;{{ $almacen->nombre }}&quot;? Esto también borra el stock y los precios propios de este almacén. No se puede deshacer.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-700">
                                                Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-gray-400">Todavía no hay almacenes en esta sucursal.</td>
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

        <!-- Modal editar almacén -->
        <div x-show="abierto" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4" style="display: none;">
            <div @click.outside="abierto = false" class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-900">Editar almacén</h3>
                    <button @click="abierto = false" class="text-gray-400 hover:text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                            <path d="M18 6 6 18M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form method="POST" :action="'{{ url('/almacenes') }}/' + id" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="edit_nombre" value="Nombre" />
                        <x-text-input id="edit_nombre" name="nombre" type="text" class="mt-1.5" required x-model="nombre" />
                    </div>

                    <div>
                        <x-input-label for="edit_ubicacion" value="Ubicación (opcional)" />
                        <x-text-input id="edit_ubicacion" name="ubicacion" type="text" class="mt-1.5" x-model="ubicacion" />
                    </div>

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
    @endif
</x-app-layout>
