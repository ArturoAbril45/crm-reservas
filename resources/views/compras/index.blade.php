<x-app-layout>
    <x-slot name="header">
        Compras
    </x-slot>

    <div x-data="{
            almacenId: '',
            formaPago: 'Efectivo',
            productosPorAlmacen: {{ \Illuminate\Support\Js::from($productosPorAlmacen) }},
            items: [],
            get productos() {
                return this.almacenId ? (this.productosPorAlmacen[this.almacenId] ?? []) : [];
            },
            itemVacio() {
                return { producto_id: '', cantidades: {}, costos: {} };
            },
            presentacionesDe(productoId) {
                return this.productos.find(p => p.id == productoId)?.presentaciones ?? [];
            },
            // Un mismo producto puede comprarse en varias presentaciones a la vez
            // (ej. 3 jabas de 12 + 5 unidades sueltas) sin necesitar líneas repetidas.
            get flatLineas() {
                const lineas = [];
                for (const item of this.items) {
                    if (!item.producto_id) continue;
                    for (const pr of this.presentacionesDe(item.producto_id)) {
                        const cantidad = Number(item.cantidades[pr.id] || 0);
                        const costo = Number(item.costos[pr.id] || 0);
                        if (cantidad > 0 && costo > 0) {
                            lineas.push({ producto_id: item.producto_id, presentacion_id: pr.id, cantidad, costo });
                        }
                    }
                }
                return lineas;
            },
            init() {
                this.items = [this.itemVacio()];
            },
            pagandoId: null,
         }">

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

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

        <!-- Lista -->
        <div class="lg:col-span-3 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden h-fit">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-900">Últimas compras</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">N°</th>
                            <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Proveedor</th>
                            <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Almacén</th>
                            <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Pago</th>
                            <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Total</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($compras as $compra)
                            <tr class="border-b border-gray-50 last:border-0">
                                <td class="px-6 py-4 text-gray-500">{{ $compra->numero }}</td>
                                <td class="px-6 py-4 font-medium text-gray-900">{{ $compra->proveedor ?? '—' }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ $compra->almacen->nombre ?? '—' }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $compra->estado_pago === 'Pendiente' ? 'bg-amber-50 text-amber-600' : 'bg-green-50 text-green-700' }}">
                                        {{ $compra->forma_pago }} · {{ $compra->estado_pago }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-900 font-medium">$ {{ number_format($compra->total, 2) }}</td>
                                <td class="px-6 py-4 text-right">
                                    @if ($compra->estado_pago === 'Pendiente')
                                        <button @click="pagandoId = {{ $compra->id }}" class="text-xs font-medium text-blue-700 hover:text-blue-800">
                                            Marcar pagada
                                        </button>
                                    @elseif ($compra->comprobante_pago)
                                        <a href="{{ asset('storage/' . $compra->comprobante_pago) }}" target="_blank" class="text-xs font-medium text-gray-500 hover:text-gray-700">Ver comprobante</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-gray-400">Todavía no hay compras registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Formulario -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-6 h-fit">
            <h2 class="font-semibold text-gray-900 mb-4">Registrar compra</h2>

            @if (! $sucursal)
                <p class="text-sm text-gray-500">Primero seleccioná una sucursal.</p>
            @else
                <form method="POST" action="{{ route('compras.store') }}" enctype="multipart/form-data" class="space-y-4" @submit="if (!flatLineas.length) $event.preventDefault()">
                    @csrf

                    <div>
                        <x-input-label for="proveedor" value="Proveedor" />
                        <x-text-input id="proveedor" name="proveedor" type="text" class="mt-1.5" :value="old('proveedor')" />
                    </div>

                    <div>
                        <x-input-label for="almacen_id" value="Almacén destino" />
                        <select id="almacen_id" name="almacen_id" x-model="almacenId" @change="items = [itemVacio()]" required
                                class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:border-blue-700 focus:outline-none focus:ring-1 focus:ring-blue-700">
                            <option value="" disabled selected>Seleccioná un almacén</option>
                            @foreach ($almacenes as $almacen)
                                <option value="{{ $almacen->id }}">{{ $almacen->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div x-show="almacenId">
                        <x-input-label value="Productos" class="mb-1.5" />
                        <template x-for="(item, i) in items" :key="i">
                            <div class="mb-3 rounded-lg border border-gray-200 p-3 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-medium text-gray-400" x-text="'Producto ' + (i + 1)"></span>
                                    <button type="button" x-show="items.length > 1" @click="items.splice(i, 1)" class="text-xs font-medium text-red-600 hover:text-red-800">Quitar</button>
                                </div>

                                <select x-model="item.producto_id" @change="item.cantidades = {}; item.costos = {}"
                                        class="block w-full rounded-lg border border-gray-300 px-3.5 py-2 text-sm">
                                    <option value="" disabled>Seleccioná un producto</option>
                                    <template x-for="p in productos" :key="p.id">
                                        <option :value="p.id" x-text="p.nombre"></option>
                                    </template>
                                </select>
                                <p class="text-xs text-gray-400" x-show="!productos.length">Ese almacén todavía no tiene productos. Creá uno primero desde Productos.</p>

                                <template x-if="item.producto_id">
                                    <div class="space-y-1.5 pt-1">
                                        <p class="text-[11px] text-gray-400">Por presentación: cuántas comprás y cuánto cuesta CADA una completa (ej. el costo de UNA jaba entera, no por unidad suelta)</p>
                                        <template x-for="pr in presentacionesDe(item.producto_id)" :key="pr.id">
                                            <div class="flex items-center gap-2">
                                                <span class="w-24 shrink-0 text-xs text-gray-600" x-text="pr.nombre + ' (x' + pr.factor + ')'"></span>
                                                <input type="number" x-model.number="item.cantidades[pr.id]" min="0" step="0.0001" placeholder="Cantidad"
                                                       class="w-20 rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs">
                                                <input type="number" x-model.number="item.costos[pr.id]" min="0" step="0.01" placeholder="Costo c/u $"
                                                       class="w-20 rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs">
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <button type="button" @click="items.push(itemVacio())" class="text-xs font-medium text-blue-700 hover:text-blue-800">
                            + Agregar producto
                        </button>

                        <template x-for="(l, idx) in flatLineas" :key="idx">
                            <span>
                                <input type="hidden" name="producto_id[]" :value="l.producto_id">
                                <input type="hidden" name="presentacion_id[]" :value="l.presentacion_id">
                                <input type="hidden" name="cantidad_presentacion[]" :value="l.cantidad">
                                <input type="hidden" name="costo_presentacion[]" :value="l.costo">
                            </span>
                        </template>
                    </div>

                    <div>
                        <x-input-label value="Forma de pago" class="mb-1.5" />
                        <input type="hidden" name="forma_pago" x-model="formaPago">
                        <div class="grid grid-cols-3 gap-2">
                            <template x-for="fp in ['Efectivo', 'Transferencia', 'Crédito']" :key="fp">
                                <button type="button" @click="formaPago = fp"
                                        class="rounded-lg border py-2 text-xs font-medium transition-colors"
                                        :class="formaPago === fp ? 'border-blue-700 text-blue-700 bg-blue-50' : 'border-gray-200 text-gray-500 hover:border-gray-300'"
                                        x-text="fp"></button>
                            </template>
                        </div>
                    </div>

                    <div x-show="formaPago === 'Transferencia'">
                        <x-input-label for="comprobante_pago" value="Comprobante de pago" />
                        <input id="comprobante_pago" name="comprobante_pago" type="file" accept="image/*,application/pdf"
                               class="mt-1.5 block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-blue-50 file:text-blue-700 file:text-sm file:font-medium">
                        <p class="mt-1.5 text-xs text-gray-400">Obligatorio para pagos por transferencia.</p>
                    </div>

                    <p class="text-xs text-gray-400" x-show="formaPago === 'Crédito'">
                        Esta compra quedará como <span class="font-medium text-amber-600">Pendiente</span> hasta que se marque como pagada adjuntando el comprobante.
                    </p>

                    <div>
                        <x-input-label for="observaciones" value="Observaciones" />
                        <textarea id="observaciones" name="observaciones" rows="2"
                                  class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:border-blue-700 focus:outline-none focus:ring-1 focus:ring-blue-700">{{ old('observaciones') }}</textarea>
                    </div>

                    <x-primary-button>Registrar compra</x-primary-button>
                </form>
            @endif
        </div>
    </div>

    <!-- Modal marcar pagada -->
    @foreach ($compras->where('estado_pago', 'Pendiente') as $compra)
        <div x-show="pagandoId === {{ $compra->id }}" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4" style="display: none;">
            <div @click.outside="pagandoId = null" class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-900">Marcar {{ $compra->numero }} como pagada</h3>
                    <button @click="pagandoId = null" class="text-gray-400 hover:text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                            <path d="M18 6 6 18M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <form method="POST" action="{{ route('compras.marcar-pagada', $compra) }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <x-input-label for="comprobante_pago_{{ $compra->id }}" value="Comprobante de pago" />
                        <input id="comprobante_pago_{{ $compra->id }}" name="comprobante_pago" type="file" accept="image/*,application/pdf" required
                               class="mt-1.5 block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-blue-50 file:text-blue-700 file:text-sm file:font-medium">
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="pagandoId = null" class="flex-1 py-2.5 rounded-lg border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50">
                            Cancelar
                        </button>
                        <x-primary-button class="flex-1 justify-center">Confirmar pago</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach

    </div>
</x-app-layout>
