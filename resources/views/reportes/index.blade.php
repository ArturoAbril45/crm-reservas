<x-app-layout>
    <x-slot name="header">
        Reportes
    </x-slot>

    <style>
        @media print {
            aside, header, form, .no-print { display: none !important; }
            main { margin: 0 !important; }
        }
    </style>

    <form method="GET" action="{{ route('reporte-ventas') }}" class="no-print bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6 flex flex-wrap items-end gap-4">
        <div>
            <x-input-label for="desde" value="Desde" />
            <input id="desde" name="desde" type="date" value="{{ $desde }}" class="mt-1.5 block rounded-lg border border-gray-300 px-3.5 py-2 text-sm text-gray-900 focus:border-blue-700 focus:outline-none focus:ring-1 focus:ring-blue-700">
        </div>
        <div>
            <x-input-label for="hasta" value="Hasta" />
            <input id="hasta" name="hasta" type="date" value="{{ $hasta }}" class="mt-1.5 block rounded-lg border border-gray-300 px-3.5 py-2 text-sm text-gray-900 focus:border-blue-700 focus:outline-none focus:ring-1 focus:ring-blue-700">
        </div>
        <div>
            <x-input-label for="sucursal_id" value="Sucursal" />
            <select id="sucursal_id" name="sucursal_id" class="mt-1.5 block rounded-lg border border-gray-300 px-3.5 py-2 text-sm text-gray-900 focus:border-blue-700 focus:outline-none focus:ring-1 focus:ring-blue-700">
                <option value="">Todas</option>
                @foreach ($sucursales as $s)
                    <option value="{{ $s->id }}" @selected((string) $sucursalId === (string) $s->id)>{{ $s->nombre }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-800">Filtrar</button>
        <button type="button" onclick="window.print()" class="rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50">
            Imprimir / PDF
        </button>
    </form>

    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Ventas ({{ $notasVentas }} notas)</p>
            <p class="text-xl font-semibold text-gray-900">$ {{ number_format($totalVentas, 2) }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Gastos</p>
            <p class="text-xl font-semibold text-red-600">$ {{ number_format($totalGastos, 2) }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Compras</p>
            <p class="text-xl font-semibold text-gray-900">$ {{ number_format($totalCompras, 2) }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Utilidad neta (ventas − gastos)</p>
            <p class="text-xl font-semibold {{ $utilidadNeta >= 0 ? 'text-green-700' : 'text-red-600' }}">$ {{ number_format($utilidadNeta, 2) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-900">Ventas por sucursal</h2>
            </div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Sucursal</th>
                        <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Notas</th>
                        <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ventasPorSucursal as $fila)
                        <tr class="border-b border-gray-50 last:border-0">
                            <td class="px-6 py-3 font-medium text-gray-900">{{ $fila->sucursal->nombre ?? '—' }}</td>
                            <td class="px-6 py-3 text-gray-500">{{ $fila->notas }}</td>
                            <td class="px-6 py-3 text-gray-900">$ {{ number_format($fila->total, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-6 py-8 text-center text-gray-400">Sin ventas en el rango.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-900">Gastos por categoría</h2>
            </div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Categoría</th>
                        <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">#</th>
                        <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($gastosPorCategoria as $fila)
                        <tr class="border-b border-gray-50 last:border-0">
                            <td class="px-6 py-3 font-medium text-gray-900">{{ $fila->categoria }}</td>
                            <td class="px-6 py-3 text-gray-500">{{ $fila->cantidad }}</td>
                            <td class="px-6 py-3 text-red-600">$ {{ number_format($fila->total, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-6 py-8 text-center text-gray-400">Sin gastos en el rango.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-900">Compras por almacén</h2>
            </div>
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Almacén</th>
                        <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">#</th>
                        <th class="px-6 py-3 text-left text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($comprasPorAlmacen as $nombre => $fila)
                        <tr class="border-b border-gray-50 last:border-0">
                            <td class="px-6 py-3 font-medium text-gray-900">{{ $nombre }}</td>
                            <td class="px-6 py-3 text-gray-500">{{ $fila['cantidad'] }}</td>
                            <td class="px-6 py-3 text-gray-900">$ {{ number_format($fila['total'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-6 py-8 text-center text-gray-400">Sin compras en el rango.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="mt-6 text-xs text-gray-400 no-print">
        Nota: la utilidad neta es operativa (ventas menos gastos). Como las notas de venta actuales no registran cantidad ni costo unitario por línea, este reporte no calcula margen de ganancia por producto — para eso habría que extender Ventas con líneas de detalle, igual que Compras.
    </p>
</x-app-layout>
