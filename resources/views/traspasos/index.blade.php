<x-app-layout>
    <x-slot name="header">
        Traspasos
    </x-slot>

    <div x-data="traspasoForm()">

    @if (session('status'))
        <div class="mb-6 px-4 py-3 rounded-xl bg-[#9c0720]/10 text-[#9c0720] text-sm border border-[#9c0720]/15">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 px-4 py-3 rounded-xl bg-red-50 text-red-700 text-sm border border-red-100">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Mi sucursal -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden h-fit">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-semibold text-gray-900">Mi sucursal</h2>
                @if ($sucursal && $otras->count())
                    <button @click="abierto = true" class="inline-flex items-center gap-2 rounded-lg bg-[#9c0720] px-3.5 py-2 text-sm font-medium text-white hover:bg-[#7c0519]">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                            <path d="M12 5v14M5 12h14" />
                        </svg>
                        Traspasar productos
                    </button>
                @endif
            </div>

            @if ($sucursal)
                <div class="px-6 py-5 flex items-center gap-4">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#9c0720]/10 text-[#9c0720] shrink-0">
                        <x-nav-icon name="store" class="h-5 w-5" />
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-gray-900">{{ $sucursal->nombre }}</p>
                        <p class="text-xs text-gray-400">{{ $sucursal->almacenes_count }} {{ $sucursal->almacenes_count === 1 ? 'almacén' : 'almacenes' }}</p>
                    </div>
                </div>
            @else
                <p class="px-6 py-8 text-center text-sm text-gray-400">Todavía no hay una sucursal seleccionada.</p>
            @endif

            @if ($traspasos->count())
                <div class="border-t border-gray-100">
                    <ul class="divide-y divide-gray-50">
                        @foreach ($traspasos as $grupo)
                            <li class="px-6 py-3 text-sm">
                                <p class="text-xs text-gray-400 mb-1">Desde {{ $grupo->first()->sucursalOrigen->nombre ?? '—' }} · {{ $grupo->first()->created_at->format('d/m/Y H:i') }}</p>
                                @foreach ($grupo as $traspaso)
                                    <p class="text-gray-900"><span class="font-medium">{{ $traspaso->producto->nombre ?? '—' }}</span> x{{ $traspaso->cantidad }}</p>
                                @endforeach
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <!-- Otras sucursales -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden h-fit">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-900">Otras sucursales</h2>
            </div>

            <ul class="divide-y divide-gray-50">
                @forelse ($otras as $otra)
                    <li class="px-6 py-3.5 flex items-center gap-4">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-gray-50 text-gray-500 shrink-0">
                            <x-nav-icon name="store" class="h-4 w-4" />
                        </span>
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $otra->nombre }}</p>
                            <p class="text-xs text-gray-400">{{ $otra->almacenes_count }} {{ $otra->almacenes_count === 1 ? 'almacén' : 'almacenes' }}</p>
                        </div>
                    </li>
                @empty
                    <li class="px-6 py-8 text-center text-sm text-gray-400">No hay otras sucursales todavía.</li>
                @endforelse
            </ul>
        </div>

    </div>

    <!-- Modal traspasar productos -->
    <div x-show="abierto" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4 py-8 overflow-y-auto" style="display: none;">
        <div @click.outside="abierto = false" class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 my-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900">Traspasar productos</h3>
                <button @click="abierto = false" class="text-gray-400 hover:text-gray-600">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('traspasos.store') }}" class="space-y-4" @submit="if (!flatLineas.length) $event.preventDefault()">
                @csrf

                <div>
                    <x-input-label for="sucursal_origen_id" value="Sucursal de origen" />
                    <select id="sucursal_origen_id" name="sucursal_origen_id" x-model="sucursalId" @change="items = [itemVacio()]" required
                            class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:border-[#9c0720] focus:outline-none focus:ring-1 focus:ring-[#9c0720]">
                        <option value="" disabled selected>Seleccioná una sucursal</option>
                        @foreach ($otras as $otra)
                            <option value="{{ $otra->id }}">{{ $otra->nombre }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-xs text-gray-400" x-show="sucursalId && !productos.length">Esa sucursal no tiene productos.</p>
                </div>

                <div>
                    <x-input-label value="Productos" class="mb-1.5" />
                    <template x-for="(item, i) in items" :key="i">
                        <div class="mb-3 rounded-lg border border-gray-200 p-3 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-medium text-gray-400" x-text="'Producto ' + (i + 1)"></span>
                                <button type="button" x-show="items.length > 1" @click="items.splice(i, 1)" class="text-xs font-medium text-red-600 hover:text-red-800">Quitar</button>
                            </div>

                            <select x-model="item.producto_id" @change="item.cantidades = {}" :disabled="!sucursalId" required
                                    class="block w-full rounded-lg border border-gray-300 px-3.5 py-2 text-sm disabled:bg-gray-50">
                                <option value="" disabled>Seleccioná un producto</option>
                                <template x-for="p in productos" :key="p.id">
                                    <option :value="p.id" x-text="p.nombre + ' — stock: ' + p.stock"></option>
                                </template>
                            </select>

                            <template x-if="item.producto_id">
                                <div class="space-y-1.5 pt-1">
                                    <p class="text-[11px] text-gray-400">Cantidad a traspasar, por presentación (dejá en 0 lo que no aplique):</p>
                                    <template x-for="pr in presentacionesDe(item.producto_id)" :key="pr.id">
                                        <div class="flex items-center gap-2">
                                            <span class="w-32 shrink-0 text-xs text-gray-600" x-text="pr.nombre + ' (x' + pr.factor + ')'"></span>
                                            <input type="number" x-model.number="item.cantidades[pr.id]" min="0" step="0.0001" placeholder="0"
                                                   class="w-24 rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs">
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>

                    <button type="button" @click="items.push(itemVacio())" class="text-xs font-medium text-[#9c0720] hover:text-[#7c0519]">
                        + Agregar producto
                    </button>
                    <p class="mt-1.5 text-xs text-gray-400">Podés combinar distintas presentaciones del mismo producto (ej. 2 jabas de 12 + 5 unidades sueltas).</p>

                    <template x-for="(l, idx) in flatLineas" :key="idx">
                        <span>
                            <input type="hidden" name="producto_id[]" :value="l.producto_id">
                            <input type="hidden" name="presentacion_id[]" :value="l.presentacion_id">
                            <input type="hidden" name="cantidad_presentacion[]" :value="l.cantidad">
                        </span>
                    </template>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" @click="abierto = false" class="flex-1 py-2.5 rounded-lg border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <x-primary-button class="flex-1 justify-center">Traspasar</x-primary-button>
                </div>
            </form>
        </div>
    </div>

    </div>

    <script>
        function traspasoForm() {
            return {
                abierto: false,
                sucursalId: '',
                productosPorSucursal: {{ \Illuminate\Support\Js::from($productosPorSucursal) }},
                items: [],
                get productos() {
                    return this.sucursalId ? (this.productosPorSucursal[this.sucursalId] ?? []) : [];
                },
                itemVacio() {
                    return { producto_id: '', cantidades: {} };
                },
                presentacionesDe(productoId) {
                    return this.productos.find(p => p.id == productoId)?.presentaciones ?? [];
                },
                // Un mismo producto puede traspasar varias presentaciones a la vez
                // (ej. 2 jabas + 5 unidades sueltas) sin necesitar líneas repetidas.
                get flatLineas() {
                    const lineas = [];
                    for (const item of this.items) {
                        if (!item.producto_id) continue;
                        for (const pr of this.presentacionesDe(item.producto_id)) {
                            const cantidad = Number(item.cantidades[pr.id] || 0);
                            if (cantidad > 0) {
                                lineas.push({ producto_id: item.producto_id, presentacion_id: pr.id, cantidad });
                            }
                        }
                    }
                    return lineas;
                },
                init() {
                    this.items = [this.itemVacio()];
                },
            };
        }
    </script>
</x-app-layout>
