<x-app-layout>
    <x-slot name="header">
        Venta
    </x-slot>

    <div x-data="ventaForm()">

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

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

        <!-- Lista -->
        <div class="lg:col-span-3 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden h-fit">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-900">Últimas ventas</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">N°</th>
                            <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Pago</th>
                            <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Jornada</th>
                            <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Total</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($ventas as $venta)
                            <tr class="border-b border-gray-50">
                                <td class="px-6 py-4 font-medium text-gray-900">{{ $venta->numero ?? ('#' . $venta->id) }}</td>
                                <td class="px-6 py-4 text-gray-500 capitalize">
                                    {{ $venta->tipo_pago }}
                                    @if ($venta->captura_pago)
                                        <a href="{{ asset('storage/' . $venta->captura_pago) }}" target="_blank" class="ml-1 text-xs font-medium text-[#9c0720] hover:text-[#7c0519]">Ver captura</a>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-gray-500">{{ optional($venta->fecha)->format('d/m/Y') ?? $venta->created_at->format('d/m/Y') }}</td>
                                <td class="px-6 py-4 text-gray-900 font-medium">$ {{ number_format($venta->valor_total, 2) }}</td>
                                <td class="px-6 py-4 text-right">
                                    <button @click="detalleAbierto = (detalleAbierto === {{ $venta->id }} ? null : {{ $venta->id }})" class="text-xs font-medium text-[#9c0720] hover:text-[#7c0519]">
                                        Ver nota
                                    </button>
                                </td>
                            </tr>
                            <tr x-show="detalleAbierto === {{ $venta->id }}" x-cloak class="border-b border-gray-50 bg-gray-50/60">
                                <td colspan="5" class="px-6 py-4">
                                    <table class="w-full text-xs">
                                        <thead>
                                            <tr class="text-gray-400 uppercase tracking-wider">
                                                <th class="text-left py-1">Producto</th>
                                                <th class="text-left py-1">Presentación</th>
                                                <th class="text-left py-1">Cantidad</th>
                                                <th class="text-left py-1">Precio</th>
                                                <th class="text-left py-1">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($venta->detalles as $detalle)
                                                <tr class="border-t border-gray-100">
                                                    <td class="py-1.5 font-medium text-gray-800">{{ $detalle->producto->nombre ?? '—' }}</td>
                                                    <td class="py-1.5 text-gray-500">{{ $detalle->presentacion }}</td>
                                                    <td class="py-1.5 text-gray-500">{{ rtrim(rtrim($detalle->cantidad_presentacion, '0'), '.') }}</td>
                                                    <td class="py-1.5 text-gray-500">$ {{ number_format($detalle->precio_unitario, 2) }}</td>
                                                    <td class="py-1.5 text-gray-900 font-medium">$ {{ number_format($detalle->total, 2) }}</td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="5" class="py-2 text-gray-400">Sin líneas (venta antigua).</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                    @if ($venta->observaciones)
                                        <p class="mt-2 text-xs text-gray-500">Obs: {{ $venta->observaciones }}</p>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-gray-400">Todavía no hay ventas registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Formulario -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-6 h-fit">
            <h2 class="font-semibold text-gray-900 mb-4">Registrar venta</h2>

            @if ($sucursales->isEmpty())
                <p class="text-sm text-gray-500">Primero creá una sucursal.</p>
            @else
                <form method="POST" action="{{ route('ventas.store') }}" enctype="multipart/form-data" class="space-y-4" @submit="if (!flatLineas.length) $event.preventDefault()">
                    @csrf

                    <div>
                        <x-input-label for="sucursal_id" value="Sucursal" />
                        <select id="sucursal_id" name="sucursal_id" x-model="sucursalId" @change="items = [itemVacio()]" required
                                class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:border-[#9c0720] focus:outline-none focus:ring-1 focus:ring-[#9c0720]">
                            <option value="" disabled>Seleccioná una sucursal</option>
                            @foreach ($sucursales as $s)
                                <option value="{{ $s->id }}">{{ $s->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label value="Jornada de la venta" class="mb-1.5" />
                        @if ($jornada['ayer_habilitado'])
                            <select name="fecha_jornada" class="block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:border-[#9c0720] focus:outline-none focus:ring-1 focus:ring-[#9c0720]">
                                @foreach ($jornada['opciones'] as $valor => $etiqueta)
                                    <option value="{{ $valor }}">{{ $etiqueta }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1.5 text-xs text-gray-400">Antes de las 2:00 a. m. podés asignar la venta al día anterior.</p>
                        @else
                            <input type="hidden" name="fecha_jornada" value="{{ $jornada['hoy'] }}">
                            <p class="rounded-lg bg-gray-50 px-3.5 py-2.5 text-sm text-gray-600">Hoy — {{ $jornada['hoy'] }}</p>
                        @endif
                    </div>

                    <!-- Productos -->
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
                                        <option :value="p.id" x-text="p.nombre + (p.controla_stock ? (' — stock: ' + p.stock) : ' — servicio')"></option>
                                    </template>
                                </select>

                                <template x-if="item.producto_id">
                                    <div class="space-y-1.5 pt-1">
                                        <p class="text-[11px] text-gray-400">Cantidad que se lleva, por presentación (dejá en 0 lo que no aplique):</p>
                                        <template x-for="pr in presentacionesDe(item.producto_id)" :key="pr.id">
                                            <div class="flex items-center gap-2">
                                                <span class="w-32 shrink-0 text-xs text-gray-600" x-text="pr.nombre + ' — $' + precioDe(item.producto_id, pr.id)"></span>
                                                <input type="number" x-model.number="item.cantidades[pr.id]" min="0" step="0.0001" placeholder="0"
                                                       class="w-24 rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs">
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                <p class="text-xs text-gray-500 text-right" x-text="'Subtotal: $ ' + subtotalItem(item).toFixed(2)"></p>
                            </div>
                        </template>

                        <button type="button" @click="items.push(itemVacio())" class="text-xs font-medium text-[#9c0720] hover:text-[#7c0519]">
                            + Agregar producto
                        </button>

                        <template x-for="(l, idx) in flatLineas" :key="idx">
                            <span>
                                <input type="hidden" name="producto_id[]" :value="l.producto_id">
                                <input type="hidden" name="presentacion_id[]" :value="l.presentacion_id">
                                <input type="hidden" name="cantidad_presentacion[]" :value="l.cantidad">
                            </span>
                        </template>
                    </div>

                    <div class="flex items-center justify-between rounded-lg bg-[#9c0720]/10 px-3.5 py-3">
                        <span class="text-sm font-medium text-[#9c0720]">Total</span>
                        <span class="text-lg font-semibold text-[#9c0720]" x-text="'$ ' + totalGeneral.toFixed(2)"></span>
                    </div>

                    <div>
                        <x-input-label value="Forma de pago" class="mb-1.5" />
                        <input type="hidden" name="tipo_pago" x-model="tipoPago">
                        <div class="grid grid-cols-2 gap-3">
                            <button type="button" @click="tipoPago = 'efectivo'"
                                    class="flex flex-col items-center gap-2 rounded-xl border py-4 transition-colors"
                                    :class="tipoPago === 'efectivo' ? 'border-[#9c0720] text-[#9c0720]' : 'border-gray-200 text-gray-500 hover:border-gray-300'">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                                    <rect x="2" y="6" width="20" height="12" rx="2" />
                                    <circle cx="12" cy="12" r="2.5" />
                                    <path d="M6 6v0M18 18v0" />
                                </svg>
                                <span class="text-sm font-medium">Efectivo</span>
                            </button>
                            <button type="button" @click="tipoPago = 'transferencia'"
                                    class="flex flex-col items-center gap-2 rounded-xl border py-4 transition-colors"
                                    :class="tipoPago === 'transferencia' ? 'border-[#9c0720] text-[#9c0720]' : 'border-gray-200 text-gray-500 hover:border-gray-300'">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-6 w-6">
                                    <path d="M3 10h18M7 15h.01M11 15h4M4 6h16a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1Z" />
                                </svg>
                                <span class="text-sm font-medium">Transferencia</span>
                            </button>
                        </div>
                    </div>

                    <div x-show="tipoPago === 'transferencia'">
                        <x-input-label for="captura_pago" value="Captura de pago" />
                        <input id="captura_pago" name="captura_pago" type="file" accept="image/*"
                               class="mt-1.5 block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-[#9c0720]/10 file:text-[#9c0720] file:text-sm file:font-medium">
                    </div>

                    <div>
                        <x-input-label for="observaciones" value="Observaciones" />
                        <textarea id="observaciones" name="observaciones" rows="2"
                                  class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:border-[#9c0720] focus:outline-none focus:ring-1 focus:ring-[#9c0720]"></textarea>
                    </div>

                    <x-primary-button>Registrar</x-primary-button>
                </form>
            @endif
        </div>
    </div>

    </div>

    <script>
        function ventaForm() {
            return {
                sucursalId: '{{ $sucursal->id ?? '' }}',
                tipoPago: 'efectivo',
                detalleAbierto: null,
                productosPorSucursal: {{ \Illuminate\Support\Js::from($productosPorSucursal) }},
                items: [],
                get productos() {
                    return this.sucursalId ? (this.productosPorSucursal[this.sucursalId] ?? []) : [];
                },
                itemVacio() {
                    return { producto_id: '', cantidades: {} };
                },
                productoDe(id) {
                    return this.productos.find(p => p.id == id) ?? null;
                },
                presentacionesDe(productoId) {
                    return this.productoDe(productoId)?.presentaciones ?? [];
                },
                precioDe(productoId, presentacionId) {
                    const producto = this.productoDe(productoId);
                    const pres = this.presentacionesDe(productoId).find(p => p.id == presentacionId);
                    return Number(pres?.precio ?? producto?.precio ?? 0);
                },
                // Cada producto puede vender varias presentaciones a la vez (ej. 2 jabas + 5 unidades
                // sueltas del mismo producto) sin necesidad de crear un producto por cada unidad.
                subtotalItem(item) {
                    return this.presentacionesDe(item.producto_id).reduce((sum, pr) => {
                        const cantidad = Number(item.cantidades[pr.id] || 0);
                        return sum + cantidad * this.precioDe(item.producto_id, pr.id);
                    }, 0);
                },
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
                get totalGeneral() {
                    return this.items.reduce((sum, item) => sum + this.subtotalItem(item), 0);
                },
                init() {
                    this.items = [this.itemVacio()];
                },
            };
        }
    </script>
</x-app-layout>
