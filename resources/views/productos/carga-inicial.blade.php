<x-app-layout>
    <x-slot name="header">
        Carga inicial de inventario
    </x-slot>

    <div class="mb-6 flex items-center justify-between gap-3 flex-wrap">
        <p class="text-sm text-gray-500">Registrá lo que ya existe físicamente en cada local. Esto no crea compras, gastos ni movimientos de caja.</p>
        <a href="{{ route('productos') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-blue-700 hover:text-blue-800">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                <path d="m12 19-7-7 7-7M19 12H5" />
            </svg>
            Volver a productos
        </a>
    </div>

    @if (session('status'))
        <div class="mb-6 px-4 py-3 rounded-xl bg-blue-50 text-blue-700 text-sm border border-blue-100">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 px-4 py-3 rounded-xl bg-red-50 text-red-700 text-sm border border-red-100">
            {{ $errors->first() }}
        </div>
    @endif

    <div x-data="cargaInicial()">
        <form method="POST" action="{{ route('productos.carga-inicial.store') }}" @submit="if (!aplicarList().length) $event.preventDefault()">
            @csrf

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <x-input-label for="almacen_id" value="Local / almacén" />
                    <select id="almacen_id" name="almacen_id" required
                            class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:border-blue-700 focus:outline-none focus:ring-1 focus:ring-blue-700">
                        <option value="" disabled {{ $almacenActual ? '' : 'selected' }}>Seleccioná un almacén</option>
                        @foreach ($almacenes as $a)
                            <option value="{{ $a->id }}" @selected($almacenActual && $almacenActual->id === $a->id)>{{ $a->nombre }} — {{ $a->sucursal->nombre ?? '—' }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="fecha" value="Fecha de conteo" />
                    <x-text-input id="fecha" name="fecha" type="date" class="mt-1.5" required :value="old('fecha', now()->toDateString())" />
                </div>
                <div>
                    <x-input-label for="observacion" value="Observación general" />
                    <x-text-input id="observacion" name="observacion" type="text" maxlength="300" class="mt-1.5" placeholder="Ej. conteo físico inicial" :value="old('observacion')" />
                </div>
            </div>

            @if ($productos->isEmpty())
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center text-gray-500 mb-6">
                    No hay productos que controlen stock todavía.
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                    @foreach ($productos as $producto)
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4"
                             :class="aplicar[{{ $producto->id }}] ? 'ring-2 ring-blue-600' : ''">
                            <div class="flex items-start gap-2.5 mb-3">
                                <input type="checkbox" x-model="aplicar[{{ $producto->id }}]" class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-700 focus:ring-blue-700">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-900 truncate">{{ $producto->nombre }}</p>
                                    <p class="text-xs text-gray-400">{{ $producto->codigo }}</p>
                                </div>
                            </div>

                            <div class="space-y-2">
                                @foreach ($producto->presentaciones as $presentacion)
                                    <div class="flex items-center justify-between gap-2 rounded-lg bg-gray-50 px-2.5 py-1.5">
                                        <div class="min-w-0">
                                            <p class="text-xs font-medium text-gray-700 truncate">{{ $presentacion->nombre }}</p>
                                            <p class="text-[10px] text-gray-400">contiene {{ rtrim(rtrim($presentacion->factor, '0'), '.') }} {{ $presentacion->factor == 1 ? 'unidad' : 'unidades' }}</p>
                                        </div>
                                        <input type="number" min="0" step="0.01" placeholder="0"
                                               x-model.number="cantidades['{{ $producto->id }}_{{ $presentacion->id }}']"
                                               class="w-16 shrink-0 rounded-lg border border-gray-300 px-2 py-1 text-xs">
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-3 pt-2 border-t border-gray-100 flex items-center justify-between">
                                <span class="text-[11px] text-gray-400">Total físico</span>
                                <strong class="text-sm text-gray-900" x-text="totalFisico({{ $producto->id }}, {{ \Illuminate\Support\Js::from($producto->presentaciones->pluck('factor', 'id')) }}) + ' unidades'"></strong>
                            </div>
                        </div>
                    @endforeach
                </div>

                <template x-for="(l, idx) in flatLineas()" :key="idx">
                    <span>
                        <input type="hidden" name="producto_id[]" :value="l.producto_id">
                        <input type="hidden" name="presentacion_id[]" :value="l.presentacion_id">
                        <input type="hidden" name="cantidad[]" :value="l.cantidad">
                    </span>
                </template>
                <template x-for="id in aplicarList()" :key="id">
                    <input type="hidden" name="aplicar_producto[]" :value="id">
                </template>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center justify-between gap-4 flex-wrap">
                    <p class="text-sm text-gray-500">Solo se van a modificar los productos marcados con la casilla. Los demás conservan su stock actual.</p>
                    <x-primary-button>Guardar saldo inicial</x-primary-button>
                </div>
            @endif
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mt-6">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900">Últimos saldos iniciales registrados</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Fecha</th>
                        <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Local</th>
                        <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Producto</th>
                        <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Anterior</th>
                        <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Nuevo</th>
                        <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Diferencia</th>
                        <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Detalle</th>
                        <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Usuario</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($historial as $item)
                        <tr class="border-b border-gray-50 last:border-0">
                            <td class="px-6 py-3 text-gray-500">{{ $item->fecha->format('d/m/Y') }}</td>
                            <td class="px-6 py-3 text-gray-500">{{ $item->almacen->nombre ?? '—' }}</td>
                            <td class="px-6 py-3 font-medium text-gray-900">{{ $item->producto->nombre ?? '—' }}</td>
                            <td class="px-6 py-3 text-gray-500">{{ $item->stock_anterior }}</td>
                            <td class="px-6 py-3 font-medium text-gray-900">{{ $item->stock_nuevo }}</td>
                            <td class="px-6 py-3 {{ $item->diferencia >= 0 ? 'text-green-700' : 'text-red-600' }}">{{ $item->diferencia >= 0 ? '+' : '' }}{{ $item->diferencia }}</td>
                            <td class="px-6 py-3 text-gray-500">{{ $item->detalle }}</td>
                            <td class="px-6 py-3 text-gray-500">{{ $item->usuario->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-10 text-center text-gray-400">Todavía no existen saldos iniciales registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function cargaInicial() {
            return {
                aplicar: {},
                cantidades: {},
                totalFisico(productoId, factores) {
                    let total = 0;
                    for (const [presentacionId, factor] of Object.entries(factores)) {
                        const cantidad = Number(this.cantidades[productoId + '_' + presentacionId] || 0);
                        total += cantidad * Number(factor);
                    }
                    return Math.round(total * 100) / 100;
                },
                flatLineas() {
                    const lineas = [];
                    for (const key in this.cantidades) {
                        const cantidad = Number(this.cantidades[key] || 0);
                        if (cantidad <= 0) continue;
                        const [productoId, presentacionId] = key.split('_');
                        if (!this.aplicar[productoId]) continue;
                        lineas.push({ producto_id: productoId, presentacion_id: presentacionId, cantidad });
                    }
                    return lineas;
                },
                aplicarList() {
                    return Object.keys(this.aplicar).filter(id => this.aplicar[id]);
                },
            };
        }
    </script>
</x-app-layout>
